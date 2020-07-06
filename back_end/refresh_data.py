import requests
import os
from zipfile import ZipFile
from pathlib import Path

# Config
site_link = 'http://localhost:80/'
target_dir = '/Users/mambo/Downloads/python-taxonomy/'
source_url = 'https://www.itis.gov/downloads/itisMySQLBulk.zip'
mysql_host = 'localhost'
mysql_user = 'root'
mysql_password = 'root'

# Downloading the archive
Path(target_dir).mkdir(parents=True, exist_ok=True)

archive_name = target_dir + 'archive.zip'

request = requests.get(source_url)

if os.path.exists(archive_name):

    with open(archive_name, 'rb') as file:
        old_archive_content = file.read()

    if request.content == old_archive_content:
        raise SystemExit('No need to refresh data')

with open(archive_name, 'wb') as file:
    file.write(request.content)


# Unzipping the file
with ZipFile(archive_name, 'r') as zip_file:

    files = zip_file.namelist()
    directory_name = files[0]
    directory_name = directory_name[0:directory_name.find('/')]

    zip_file.extractall(target_dir)

# Putting new data into the database
os.system('cd %s && mysql -h%s -u%s -p%s < %s' % (
    directory_name,
    mysql_host,
    mysql_user,
    mysql_password,
    'CreateDB.sql'))

# Extract the data

queries = ['kingdoms', 'ranks', 'rows']

for query in queries:
    with open('sql/' + query + '.sql', 'r') as file:
        result_query = file.read()

    result_query = result_query.replace("\n", ' ')
    result_query = result_query.replace("`", '\\' + '`')

    os.system('mysql -h%s -u%s -p%s -e "%s" > %s' % (
        mysql_host,
        mysql_user,
        mysql_password,
        result_query,
        target_dir + query + '.csv'
    ))

# Let PHP handle the rest -_-
requests.get(url=site_link+'cron/refresh_data/')


# Converting Kingdoms data from CSV to JSON
# kingdoms = []
# with open(target_dir + 'kingdoms.csv') as file:
#     line = file.readline()
#     while True:
#         line = file.readline()
#
#         if not line:
#             break
#
#         line.strip()
#         line = line.split("\t")
#
#         kingdoms[int(line[0])] = line[1]
#
# kingdoms = json.dumps(kingdoms)
# with open(target_dir + 'kingdoms.json', 'w') as file:
#     file.write(kingdoms)
#
# # Converting Ranks data from CSV to JSON
# ranks = {}
# with open(target_dir + 'ranks.csv') as file:
#     line = file.readline()
#     while True:
#         line = file.readline()
#
#         if not line:
#             break
#
#         line.strip()
#         line = line.split("\t")
#
#         kingdom = int(line[0])
#         rank = int(line[1])
#
#         if kingdom not in ranks:
#             ranks[kingdom] = {}
#
#         ranks[kingdom][rank] = [line[2], line[3]]
#
# ranks = json.dumps(ranks)
# with open(target_dir + 'ranks.json', 'w') as file:
#     file.write(ranks)


# # Converting Rows data from CSV to JSON
# rows = {}
# with open(target_dir + 'rows.csv') as file:
#     line = file.readline()
#     while True:
#         line = file.readline()
#
#         if not line:
#             break
#
#         line.strip()
#         line = line.split("\t")
#
#         kingdom = int(line[0])
#         rank = int(line[1])
#
#         if kingdom not in ranks:
#             rows[kingdom] = {}
#
#         rows[kingdom][rank] = [line[2], line[3]]
#
# rows = json.dumps(ranks)
# with open(target_dir + 'rows.json', 'w') as file:
#     file.write(ranks)
