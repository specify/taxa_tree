import hashlib
import json
import time
import os
from zipfile import ZipFile
from pathlib import Path
from config import target_dir, mysql_host, mysql_user, mysql_password, \
    mysql_command, mysql_database, docker_dir

#
begin_time = time.time()
print('Preparation')
Path(target_dir).mkdir(parents=True, exist_ok=True)

#
print('Checking if archive changed')

hash_destination = os.path.join(target_dir, 'hash.xml')

old_hash = ''
if os.path.exists(hash_destination):
    with open(hash_destination) as file:
        old_hash = file.read()
        
archive_name = os.path.join(target_dir, 'archive.zip')

hasher = hashlib.md5()
with open(archive_name, 'rb') as file:
    buf = file.read()
    hasher.update(buf)
new_hash = hasher.hexdigest()

if new_hash == old_hash:
    print('No need to update data')
    exit(0)

with open(hash_destination, 'w') as file:
    file.write(new_hash)

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
assert os.system(command) == 0

#
print('Putting new data into the database')
print('[taxon] (this would take some time)')
assert os.system(
    '%s -h%s -u%s -p%s --database %s -e "LOAD DATA LOCAL INFILE \'%s\' INTO TABLE taxon IGNORE 1 LINES;"' % (
        mysql_command,
        mysql_host,
        mysql_user,
        mysql_password,
        mysql_database,
        os.path.join(docker_dir, 'extracted/taxon.txt')
    )
) == 0

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
assert os.system(
    f'{mysql_command} '
    f'-u{mysql_user} '
    f'-p{mysql_password} '
    f'-h{mysql_host} '
    f'-e \'{result_query} INTO OUTFILE "{out_file}" FIELDS TERMINATED BY "\t"\''
) == 0

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


kingdoms = {
    'urn:lsid:marinespecies.org:taxname:2': 'Animalia'
}
columns = list_flip([
    'tsn', 'name', 'common_name', 'parent_tsn', 'rank', 'author', 'source'
])
line_number = 0
# It's super important that you provide all ranks that are used in the file in this list
# Extra ranks are fine (as long as they are in order)
# If some rank is not provided in here, it, along with it's subtree will be thrown out (which is a really bad design)
specify_ranks = [
    "Domain",
    "Kingdom",
    "Subkingdom",
    "Infrakingdom",
    "Superphylum",
    "Superdivision",
    "Phylum",
    "Division",
    "Subphylum",
    "Subdivision",
    "Infraphylum",
    "Infradivision",
    "Parvphylum",
    "Gigaclass",
    "Megaclass",
    "Superclass",
    "Class",
    "Subclass",
    "Infraclass",
    "Subterclass",
    "Superorder",
    "Cohort",
    "Order",
    "Suborder",
    "Infraorder",
    "Parvorder",
    "Section",
    "Subsection",
    "Superfamily",
    "Epifamily",
    "Family",
    "Subfamily",
    "Supertribe",
    "Tribe",
    "Subtribe",
    "Genus",
    "Subgenus",
    "Species",
    "Subspecies",
    "Natio",
    "Variety",
    "Subvariety",
    "Forma",
    "Subforma",
    "Mutatio"
]
rows = {
    'urn:lsid:marinespecies.org:taxname:2': [
        [
            'Animalia',
            'Animalia',
            '',
            ''
        ],
        specify_ranks.index('Kingdom') + 1,
        [],
        None
    ]
}

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
print('Finding parent nodes')

modified = True
while modified:
    modified = False

    for tsn, row in rows.items():

        if len(row) < 4:
            continue

        parent_tsn = row[3]

        if parent_tsn not in rows:
            continue

        if row[1] < rows[parent_tsn][1]:
            print("Skipping node with wrong rank order: " + row[0][3])
            continue

        del row[3]
        rows[parent_tsn][2].append(tsn)

        modified = True

#         print('.')

print('Filtering out nodes without parents')
kingdom_rank_id = specify_ranks.index('Kingdom') + 1
rows = {
    tsn:row
    for tsn,row in rows.items()
    if len(row) < 4 or row[1]==kingdom_rank_id
}

print('Rows: %d\n' % line_number)

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
