import requests
import xml.etree.ElementTree as xmlParser
import json
import time
from zipfile import ZipFile
from pathlib import Path
from os import system, path

#
print('Config')
site_link = 'http://localhost:80/'
target_dir = '/Users/mambo/Downloads/gbif_col/'
source_url = 'http://www.catalogueoflife.org/DCA_Export/zip/archive-complete.zip'
meta_url = 'https://api.gbif.org/v1/dataset/7ddf754f-d193-4cc9-b351-99906754a03b/document'
mysql_host = 'localhost'
mysql_user = 'root'
mysql_password = 'root'

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
        raise SystemExit('No need to refresh data')

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
system('mysql -h%s -u%s -p%s --database gbif_col -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE taxa IGNORE 1 LINES;"' % (
    mysql_host,
    mysql_user,
    mysql_password,
    target_dir+'extracted/taxa.txt'))


#
print('Extracting data (this may take some time)')
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
line_number = 0
specify_ranks = ['Domain', 'Infrakingdom', 'Superphylum', 'Infradivision', 'Cohort', 'Kingdom', 'Subkingdom', 'Division',
               'Subdivision', 'Phylum', 'Subphylum', 'Superclass', 'Class', 'Subclass', 'Infraclass', 'Superorder',
               'Order', 'Suborder', 'Infraorder', 'Superfamily', 'Family', 'Subfamily', 'Tribe', 'Subtribe', 'Genus',
               'Subgenus', 'Section', 'Subsection', 'Species', 'Subspecies', 'Variety', 'Subvariety', 'Forma',
               'Subforma']

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

    # create kingdom
    if kingdom not in kingdoms:

        if not kingdoms:
            kingdom_id = 1
        else:
            kingdom_id = kingdoms[max(kingdoms, key=kingdoms.get)] + 1

        kingdoms[kingdom] = kingdom_id

        ranks[kingdom_id] = {}
        rows[kingdom_id] = {}

    else:
        kingdom_id = kingdoms[kingdom]

    # create rank
    if rank not in ranks[kingdom_id]:

        if not ranks[kingdom_id]:
            rank_id = 1
        else:
            rank_id = ranks[kingdom_id][max(ranks[kingdom_id], key=ranks[kingdom_id].get)] + 1

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

    print(str(line_number) + "\t" + data[0][0])
    line_number = line_number + 1

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
new_ranks = {}  # fix ranks being in wrong order
for kingdom_id, kingdom_ranks in ranks.items():

    new_ranks[kingdom_id] = {}
    parent_rank_id = 0

    for rank_name in specify_ranks:

        lower_case_rank_name = rank_name.lower()

        if lower_case_rank_name not in kingdom_ranks:
            continue

        rank_id = kingdom_ranks[lower_case_rank_name]
        new_ranks[kingdom_id][rank_id] = [rank_name, parent_rank_id]

        parent_rank_id = rank_id

ranks = new_ranks

with open(ranks_data, 'w') as file:
    file.write(json.dumps(ranks))

raise SystemExit

order_line_number = 0

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

            print('Fixed order')
            order_line_number = order_line_number + 1

print('Rows: %d\nOrder fixes: %d' % (line_number, order_line_number))

#
print('Saving data')
Path(rows_destination).mkdir(parents=True, exist_ok=True)

for kingdom_id, rows_data in rows.items():
    with open(rows_destination + str(kingdom_id) + '.json', 'w') as file:
        rows_data['root'] = root[kingdom_id]
        file.write(json.dumps(rows_data))

print('Begin time: %f' % begin_time)
print('End time: %f' % time.time())
