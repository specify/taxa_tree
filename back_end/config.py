site_link = 'http://localhost:80/'
target_dir = '/Users/maxxxxxdlp/Downloads/worms_taxon'
mysql_host = 'localhost'
mysql_user = 'root'
mysql_password = 'root'


mysql_mode = 'docker'
mysql_database = 'worms'

if mysql_mode == 'docker':
    docker_container = 'test'
    mysql_command = f'docker exec {docker_container} mysql'
    docker_dir = '/sql/worms_taxon/'
else:
    docker_container = ''
    mysql_command = 'mysql'
    docker_dir = target_dir
