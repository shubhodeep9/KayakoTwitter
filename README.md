# KayakoTwitter
A simple Twitter API client in PHP. This client simply has to fetch and display Tweets that <br />
a) Have been re-Tweeted at least once and<br /> b) Contain the hashtag #custserv

## Scripts
<ul>
<li>PHP => index.php</li>
<li>Python => main.py</li>
</ul>

## Public Ports
For better readability, open these using Postman<br />
[GET] http://gdgbasics.cloudapp.net/KayakoTwitter/index.php [PHP Script]<br />
[GET] http://gdgbasics.cloudapp.net:8888/ [Python Script]<br />

## Installation
<strong>Make sure to put your Twitter App Tokens as environment variables.</strong>
<ul>
<li>PHP - Put the script in your apache htdocs folder, and make sure php-curl is installed.</li>
<li>Python - Install dependencies using</li>
</ul>
```sh
$ sudo pip install -r requirements.txt
```
Then run
```sh
$ python main.py
```
This will make the script run on port 8888.
