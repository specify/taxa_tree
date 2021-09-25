import requests
import xml.etree.ElementTree as xmlParser
import json
import time
import os
from zipfile import ZipFile
from pathlib import Path
from config import target_dir, mysql_host, mysql_user, mysql_password, \
    mysql_command, docker_dir

#

get_source_url = lambda date:\
    f'https://download.catalogueoflife.org/col/monthly/{date}_coldp.zip'
meta_url = 'https://api.gbif.org/v1/dataset/7ddf754f-d193-4cc9-b351-99906754a03b/document'

#
begin_time = time.time()
print('Preparation')
Path(target_dir).mkdir(parents=True, exist_ok=True)

#
print('Downloading meta data')

date_destination = os.path.join(target_dir, 'date.txt')
meta_destination = os.path.join(target_dir, 'meta.xml')
temp_meta_destination = os.path.join(target_dir, 'meta_temp.xml')

request = requests.get(meta_url)


def get_date(target_destination):
    tree = xmlParser.parse(target_destination)
    root_element = tree.getroot()
    description = root_element.find('dataset')
    return description.find('pubDate').text.strip()


with open(temp_meta_destination, 'wb') as file:
    file.write(request.content)
new_data_date = get_date(temp_meta_destination)

if os.path.exists(meta_destination):
    old_data_date = get_date(meta_destination)
    if old_data_date == new_data_date:
        raise SystemExit('No need to refresh data')

with open(meta_destination, 'wb') as file:
    file.write(request.content)

with open(date_destination, 'w') as file:
    file.write(get_date(meta_destination))

print('Downloading the archive')

archive_name = os.path.join(target_dir, 'archive.zip')

request = requests.get(get_source_url(new_data_date))

with open(archive_name, 'wb') as file:
    file.write(request.content)


#
print('Unzipping the file')
with ZipFile(archive_name, 'r') as zip_file:
    files = zip_file.namelist()
    zip_file.extractall(os.path.join(target_dir, 'extracted/'))

print('Creating database schema')
with open('sql/schema.sql', 'r') as file:
    result_query = file.read()

result_query = result_query.replace("\n", ' ')
result_query = result_query.replace("`", '\\' + '`')

command = '%s -h%s -u%s -p%s -e "%s"' % (
    mysql_command,
    mysql_host,
    mysql_user,
    mysql_password,
    result_query
)
os.system(command)

#
print('Putting new data into the database')
print('[NameUsage] (this would take some time)')
os.system(
    '%s -h%s -u%s -p%s --database col -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE NameUsage IGNORE 1 LINES;"' % (
        mysql_command,
        mysql_host,
        mysql_user,
        mysql_password,
        os.path.join(docker_dir, 'extracted/NameUsage.tsv')
    )
)

print('[NameRelation]')
os.system(
    '%s -h%s -u%s -p%s --database col -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE NameRelation IGNORE 1 LINES;"' % (
        mysql_command,
        mysql_host,
        mysql_user,
        mysql_password,
        os.path.join(docker_dir, 'extracted/NameRelation.tsv')
    )
)

print('[Reference] (this would take some time)')
os.system(
    '%s -h%s -u%s -p%s --database col -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE Reference IGNORE 1 LINES;"' % (
        mysql_command,
        mysql_host,
        mysql_user,
        mysql_password,
        os.path.join(docker_dir, 'extracted/Reference.tsv')
    )
)

#
print('Extracting data (this would take some time)')
with open('sql/rows.sql', 'r') as file:
    result_query = file.read()

result_query = result_query.replace("\n", ' ')
result_query = result_query.replace("'", '"')

rows_data = os.path.join(target_dir, 'rows.csv')
out_file = os.path.join(docker_dir, 'rows.csv')
if os.path.exists(rows_data):
    # MariaDB would throw error instead of overwriting if file exists
    os.remove(rows_data)
os.system(
    f'{mysql_command} '
    f'-u{mysql_user} '
    f'-p{mysql_password} '
    f'-h{mysql_host} '
    f'-e \'{result_query} INTO OUTFILE "{out_file}" FIELDS TERMINATED BY "\t"\''
)

#
print('Building a list of kingdoms and ranks and a tree of rows (taxon units)')
rows_destination = os.path.join(target_dir, 'rows/')


def list_flip(original_list):
    item_id = 0
    dictionary = {}

    for item in original_list:
        dictionary[item] = item_id
        item_id = item_id + 1

    return dictionary


rows = {}
kingdoms = {}
columns = list_flip([
    'tsn', 'name', 'common_name', 'parent_tsn', 'rank', 'author', 'source'
])
line_number = 0
specify_ranks = [rank.lower() for rank in [
    'Domain', 'Infrakingdom', 'Superphylum', 'Infradivision', 'Cohort',
    'Kingdom', 'Subkingdom', 'Division', 'Subdivision', 'Phylum',
    'Subphylum', 'Superclass', 'Class', 'Subclass', 'Infraclass', 'Superorder',
    'Order', 'Suborder', 'Infraorder', 'Superfamily', 'Family', 'Subfamily',
    'Tribe', 'Subtribe', 'Genus', 'Subgenus', 'Section', 'Subsection',
    'Species', 'Subspecies', 'Variety', 'Subvariety', 'Forma', 'Subforma'
]]

with open(rows_data, 'r') as rows_file:
    line = rows_file.readline()
    while True:
        line = rows_file.readline()

        if not line:
            break

        row = line.strip()
        row = row.split("\t")

        while len(row) < len(columns):
            row.append('')

        tsn = row[columns['tsn']]
        parent_tsn = row[columns['parent_tsn']]
        rank = row[columns['rank']]
        name = row[columns['name']]

        # create kingdom
        if rank == 'kingdom':
            kingdoms[tsn] = name

        if rank not in specify_ranks:
            continue

        rank_id = specify_ranks.index(rank) + 1

        if row[columns['common_name']] == name:
            row[columns['common_name']] = ''

        rows[tsn] = [
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

        print(f'{line_number}\t{name}')
        line_number = line_number + 1


#
print('Saving kingdoms')
with open(os.path.join(target_dir, 'kingdoms.json'), 'w') as file:
    file.write(json.dumps(kingdoms))

#
print('Fixing orders')
orders_fixed = 0

modified = True
while modified:
    modified = False

    for tsn, row in rows.items():

        if len(row) < 4:
            continue

        parent_tsn = row[3]

        if parent_tsn not in rows:
            continue

        del row[3]
        rows[parent_tsn][2].append(tsn)
        modified = True

        print('.')
        orders_fixed = orders_fixed + 1

print('Filtering out nodes without parents')
kingdom_rank_id = specify_ranks.index('kingdom') + 1
rows = {
    tsn:row
    for tsn,row in rows.items()
    if len(row) < 4 or row[1]==kingdom_rank_id
}

print('Rows: %d\nOrder fixes: %d' % (line_number, orders_fixed))

#
print('Saving data')
Path(rows_destination).mkdir(parents=True, exist_ok=True)

def group(tsn):
    grouped_records.add(tsn)
    kingdom_ranks.add(rows[tsn][1])
    for child in rows[tsn][2]:
        group(child)

ranks_data = {}
for kingdom_id in kingdoms.keys():
    grouped_records = set()
    kingdom_ranks = set()
    group(kingdom_id)

    with open(os.path.join(rows_destination, f'{kingdom_id}.json'), 'w') as file:
        file.write(json.dumps({
            tsn:rows
            for tsn, rows in rows.items()
            if tsn in grouped_records
        }))

    parent_rank_id = 0
    ranks_data[kingdom_id] = {}
    for rank_id, rank_name in enumerate(specify_ranks, start=1):
        if rank_id not in kingdom_ranks:
            continue
        ranks_data[kingdom_id][rank_id] = [
            rank_name[0].upper() + rank_name[1:],
            parent_rank_id
        ]
        parent_rank_id = rank_id

with open(os.path.join(target_dir, 'ranks.json'), 'w') as file:
    file.write(json.dumps(ranks_data))

print('Updated!')
print('Time taken: %fs' % (time.time() - begin_time))
