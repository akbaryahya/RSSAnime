# RSSAnime
## Download Anime lewat Console

Script ini dibuat untuk teman-teman dan saya sendiri yang sibuk dengan dunia coding yang gak punya waktu buat buka web yang banyak iklan fake link download atau main petak umpat (linknya di bikin ribet downloadnya).

## Fitur dan TODO
- Bisa pilih/multi Resolution
- Bisa pilih Sumber link download di mana (misalnya zippyshare dulu) (PS: Sumbernya masih satu)
- Bisa pilih Sumber situs Animenya download di mana (misalnya otakudesu) (PS: Sumbernya masih satu)
- Auto download kalau sudah nemu link download (tanpa harus klik manual)
- Bisa skip anime yang gak mau di download
- Bisa link folder jadi file animenya langsung di save folder tertentu

## TODO
- Banyak Sumber
- Notif kalau ada download baru lewat Discord,Telegram
- Auto save ke cloud seperti Google Drive (sementara bisa pakai rclone)
- Auto encode ulang ke 60/144fps (pakai SVP)
- Datebase pakai MongoDB buat API Sistem
- Download kyk IDM/FDM (multiple parallel connections) biar cepat downloadnya.
- Web based Docker

## Cara Pasang (Windows User)
- Download PHP di https://windows.php.net/download/ (V7.4) mau coba pakai V8^ juga bisa atau XAMPP
- Download Composer di https://getcomposer.org/download/
- Jangan lupa pastikan curl,cacert dah ada yah
- Jangan lupa di dalam folder buat juga folder "dl" karena nanti di sini file animenya di save

Setelah itu buka folder lalu klik kanan lalu pilih Windows Terminal (kalau Pakai Windows 11,sebut aja cmd)
dan ketik di bawah ini
```sh
php composer.phar install
php composer.phar update
```
Sudah itu kamu bisa pakai sekarang dengan ketik

```sh
php app.php --doing dl --resolution 1080,720 --link_source ZippyShare --limit_ongoing 10 --autodl true
```

## HELP
| Fungsi | KET |
| ------ | ------ |
| doing | adalah aksi untuk melakukan apa misalnya kalau dl artinya lagi download, kalau tes buat debug|
| resolution | buat pilih ukuran mau berapa |
| link_source | sumber downloadnya dari mana |
| limit_ongoing | buat kasih batas berapa anime yang ingin di download setiap kali di run |
| autodl | buat kasih jalan downloadnya, kalau di false hanya muncul output json lengkap dengan linknya |

## Folder 
Karena script ini dibuat simpel bangat untuk melakukan beberapa hal seperti:

- Skip Anime: buat file skip.txt tanpa di isi di dalam folder judul anime yang mau di skip
- Save Anime di Folder Lain: buat file link.txt lalu isi URL FOLDER,misalnya "\\HS1\Media1\Film\AnimeV2\AKB0048\S1 (BD) (720P)" lalu save.
