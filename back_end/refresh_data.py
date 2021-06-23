import requests
import os
from zipfile import ZipFile
from pathlib import Path
from config import site_link, target_dir, \
        mysql_host, mysql_user, mysql_password


print('Config')
source_url = 'https://www.itis.gov/downloads/itisMySQLBulk.zip'


Path(target_dir).mkdir(parents=True, exist_ok=True)


print('Downloading meta data')
etag_name = target_dir + 'etag.txt'
etag = requests.head(source_url).headers['ETag']


if os.path.isfile(etag_name):
    with open(etag_name, 'r') as file:
        current_etag = file.read()
    if etag == current_etag:
        print('No need to refresh data')
        exit(0)
else:
    with open(etag_name, 'w') as file:
        file.write(etag)


print('Downloading the archive')
archive_name = target_dir + 'archive.zip'
request = requests.get(source_url)

with open(archive_name, 'wb') as file:
    file.write(request.content)

print('Unzipping the file')
with ZipFile(archive_name, 'r') as zip_file:

    files = zip_file.namelist()
    directory_name = files[0]
    directory_name = directory_name[0:directory_name.find('/')]
    directory_name = os.path.join(target_dir, directory_name)

    zip_file.extractall(target_dir)


print('Putting new data into the database')
os.system('cd %s && mysql -h%s -u%s -p%s < %s' % (
    directory_name,
    mysql_host,
    mysql_user,
    mysql_password,
    'CreateDB.sql')
)


print('Extracting the data')

queries = ['kingdoms', 'ranks', 'rows']

for query in queries:
    print(f'Querying {query}')
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

print('Making PHP handle the rest -_-')
requests.get(url=site_link+'cron/refresh_data.php')
