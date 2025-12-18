SERVER VERSION:
PHP: 8.2.13

INSTALLATION:<br>
1). Clone Repo (Dengan syarat sudah memasukan SSH-Key)<br>
2). buka direktori projek<br>
3). run composer install<br>
4). creata database mysql dengan nama lain tapi di setting di .env berikut:<br>
    &nbsp;&nbsp;DB_CONNECTION=mysql<br>
    &nbsp;&nbsp;DB_HOST=127.0.0.1<br>
    &nbsp;&nbsp;DB_PORT=3306<br>
    &nbsp;&nbsp;DB_DATABASE=smart_hris<br>
    &nbsp;&nbsp;DB_USERNAME=root<br>
    &nbsp;&nbsp;DB_PASSWORD=<br>
6). run php artisan migrate<br>
7). run php artisan db:seed<br>
8). run php artisan serve<br>
9). test API dengan menggunakan insome ataupun postman<br>









