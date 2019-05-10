# service
包括常用的各种服务（短信、websocket、JSON Web Token）
## 可选依赖包：
本库是一个封装的api默认情况下各api的依赖包是不require，需要使用对应的api可根据下面列出来的信息进行安装。
####阿里系列：
https://github.com/search?l=PHP&q=alibabacloud&type=Repositories
<br>
精简sdk:   
composer  require  alibabacloud/sdk
######短信：
composer require alibabacloud/client
https://help.aliyun.com/document_detail/112186.html?spm=a2c4g.11186623.2.16.143f50a4hS9KcE
######OSS对象存储：
composer require aliyuncs/oss-sdk-php
https://help.aliyun.com/document_detail/85580.html?spm=a2c4g.11186623.6.797.15d533bcPsNp6Y
####时间日历：
composer require overtrue/chinese-calendar
https://github.com/XhinLiang/LunarCalendar
####七牛云存储：
composer require qiniu/php-sdk
https://developer.qiniu.com/kodo/sdk/1241/php

