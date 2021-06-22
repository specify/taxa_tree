import requests
from config import site_link, target_dir, \
        mysql_host, mysql_user, mysql_password

print('Calling php')
requests.get(url=site_link+'cron/refresh_data.php')

