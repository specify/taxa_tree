import requests
import xml.etree.ElementTree as xmlParser
import json
import time
from zipfile import ZipFile
from pathlib import Path
from os import system, path
from config import site_link, target_dir, \
        mysql_host, mysql_user, mysql_password

#
print('Config')
source_url = 'http://rs.gbif.org/datasets/backbone/backbone-current.zip'
meta_url = 'https://api.gbif.org/v1/dataset/d7dddbf4-2cf0-4f39-9b2a-bb099caae36c/document'

#
begin_time = time.time()
print('Preparation')
Path(target_dir).mkdir(parents=True, exist_ok=True)


#
print('Downloading meta data')

date_destination = target_dir + 'date.txt'
meta_destination = target_dir + 'meta.xml'
temp_meta_destination = target_dir + 'meta_temp.xml'

request = requests.get(meta_url)


def get_date(target_destination):
    tree = xmlParser.parse(target_destination)
    root_element = tree.getroot()
    description = root_element.find('dataset')
    return description.find('pubDate').text.strip()


if path.exists(meta_destination):

    with open(temp_meta_destination, 'wb') as file:
        file.write(request.content)

    old_data_date = get_date(meta_destination)
    new_data_date = get_date(temp_meta_destination)

    if old_data_date == new_data_date:
        print('No need to refresh data')
        exit(0)

with open(meta_destination, 'wb') as file:
    file.write(request.content)

with open(date_destination, 'w') as file:
    file.write(get_date(meta_destination))

print('Downloading the archive')

archive_name = target_dir + 'archive.zip'

request = requests.get(source_url)

with open(archive_name, 'wb') as file:
    file.write(request.content)


#
print('Unzipping the file')
with ZipFile(archive_name, 'r') as zip_file:
    files = zip_file.namelist()
    zip_file.extractall(target_dir+'extracted/')


#
print('Creating database schema')
with open('sql/taxa.sql', 'r') as file:
    result_query = file.read()

result_query = result_query.replace("\n", ' ')
result_query = result_query.replace("`", '\\' + '`')

system('mysql -h%s -u%s -p%s -e "%s"' % (
    mysql_host,
    mysql_user,
    mysql_password,
    result_query
))


#
print('Putting new data into the database')
system('mysql -h%s -u%s -p%s --database gbif -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE taxa IGNORE 1 LINES;"' % (
    mysql_host,
    mysql_user,
    mysql_password,
    target_dir+'extracted/Taxon.tsv'))


#
print('Extracting data')
with open('sql/rows.sql', 'r') as file:
    result_query = file.read()

result_query = result_query.replace("\n", ' ')
result_query = result_query.replace("`", '\\' + '`')

system('mysql -h%s -u%s -p%s -e "%s" > %s' % (
    mysql_host,
    mysql_user,
    mysql_password,
    result_query,
    target_dir + 'rows.csv'
))


#
print('Building a list of kingdoms and ranks and a tree of rows (taxon units)')
kingdoms_data = target_dir + 'kingdoms.json'
ranks_data = target_dir + 'ranks.json'
rows_data = target_dir + 'rows.csv'
rows_destination = target_dir + 'rows/'


def list_flip(original_list):
    item_id = 0
    dictionary = {}

    for item in original_list:
        dictionary[item] = item_id
        item_id = item_id + 1

    return dictionary


rows_file = open(rows_data, 'r')
line = rows_file.readline()
rows = {}
ranks = {}
kingdoms = {}
root = {}
columns = list_flip(['tsn', 'name', 'common_name', 'parent_tsn', 'rank', 'kingdom', 'author', 'source'])
i = 0

printing('Parsing data')
while True:
    line = rows_file.readline()

    if not line:
        break

    row = line.strip()
    row = row.split("\t")

    while len(row) < len(columns):
        row.append('')

    tsn = int(row[columns['tsn']])
    parent_tsn = int(row[columns['parent_tsn']])
    kingdom = row[columns['kingdom']]
    rank = row[columns['rank']]
    name = row[columns['name']]

    if parent_tsn == 0:  # create kingdom
        kingdom_id = int(tsn)

        kingdoms[name] = kingdom_id

        ranks[kingdom_id] = {}
        rows[kingdom_id] = {}

    else:
        kingdom_id = kingdoms[kingdom]

    if rank not in ranks[kingdom_id]:  # create rank
        rank_id = int(tsn)
        ranks[kingdom_id][rank] = rank_id

    else:
        rank_id = ranks[kingdom_id][rank]

    if row[columns['common_name']] == name:
        row[columns['common_name']] = ''

    data = [
        [
            name,
            row[columns['common_name']],
            row[columns['author']],
            row[columns['source']],
        ],
        rank_id,
        [],  # children
        parent_tsn,
    ]

    print('.', end='')
    #print(str(i)+"\t"+data[0][0])
    i = i+1

    rows[kingdom_id][tsn] = data

    if parent_tsn == 0:
        del data[columns['parent_tsn']]
        root[kingdom_id] = tsn

    elif parent_tsn in rows[kingdom_id]:
        del data[columns['parent_tsn']]
        rows[kingdom_id][parent_tsn][2].append(tsn)

rows_file.close()


print('Saving kingdoms')
new_kingdoms = {}
for kingdom_name, kingdom_id in kingdoms.items():
    new_kingdoms[kingdom_id] = kingdom_name
kingdoms = new_kingdoms

with open(kingdoms_data, 'w') as file:
    file.write(json.dumps(kingdoms))


print('Saving ranks')
new_ranks = {}
for kingdom_id, kingdom_ranks in ranks.items():

    parent_rank_id = 0
    new_ranks[kingdom_id] = {}

    for rank_name, rank_id in kingdom_ranks.items():
        new_ranks[kingdom_id][rank_id] = [rank_name.capitalize(), parent_rank_id]

        parent_rank_id = rank_id
ranks = new_ranks

with open(ranks_data, 'w') as file:
    file.write(json.dumps(ranks))

ii = 0

print('Fixing rank order')
for kingdom_id, kingdom_data in rows.items():

    modified = True

    while modified:
        modified = False

        for tsn, row in kingdom_data.items():

            try:
                parent_tsn = row[3]
            except IndexError:
                continue

            try:
                rows[kingdom_id][parent_tsn][2].append(tsn)
            except KeyError:
                continue

            modified = False

            print('.', end='')
            ii = ii+1

print('Rows: %d\nOrder fixes: %d' % (i, ii))

#
print('Saving kingdoms')
Path(rows_destination).mkdir(parents=True, exist_ok=True)

for kingdom_id, rows_data in rows.items():
    with open(rows_destination + str(kingdom_id) + '.json', 'w') as file:
        file.write(json.dumps(rows_data))

print('Begin time: %f' % begin_time)
print('End time: %f' % time.time())
