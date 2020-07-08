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
requests.get(url=site_link+'cron/refresh_data.php')
