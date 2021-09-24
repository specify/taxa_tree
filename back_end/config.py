site_link = 'http://localhost:80/'
target_dir = '/Users/maxxxxxdlp/Downloads/col_taxon'
mysql_host = 'localhost'
mysql_user = 'root'
mysql_password = 'root'


mysql_mode = 'docker'

if mysql_mode == 'docker':
    docker_container = 'test'
    mysql_command = f'docker exec {docker_container} mysql'
    mysql_export_command = f'docker exec {docker_container} mysql '
    mysql_command_copy_suffix = '\';'
    docker_dir = '/sql/col_taxon/'
else:
    docker_container = ''
    mysql_command = 'mysql'
    mysql_command_copy = 'docker exec test /bin/bash -c \'echo mysql '
    mysql_command_copy_suffix = '\';'
    docker_dir = target_dir
