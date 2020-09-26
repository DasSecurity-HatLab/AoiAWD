# 构建流程简介
尽量流程顺序进行构建，部分组件依赖额外的扩展库才可以正常构建/运行，自行参考错误信息进行安装。
## 1. 安装MongoDB Server服务器
可以通过包管理器直接从发行版获取，比如给出一个Ubuntu下的例子
```shell
sudo apt install mongodb-server
```
Ubuntu下的php-mongodb存在一些bug，请通过pecl安装并添加扩展
```shell
sudo pecl install mongodb
```

## 2. 构建Frontend项目
此步骤依赖npm
```shell
cd Frontend
npm install
npm run build
```
构建成功后将在dist/目录下得到
- index.html
- static/

## 3. 构建AoiAWD Core
此步骤依赖PHP7-CLI和部分必要的PHP扩展
```shell
cd AoiAWD
rm -rf src/public/*
cp -r ../Frontend/dist/* src/public/
php compile.php
```
构建成功后将得到
- aoiawd.phar

## 4. 构建TapeWorm
此步骤依赖PHP7-CLI和部分必要的PHP扩展
```shell
cd TapeWorm
php compile.php
```
构建成功后将得到
- tapeworm.phar

## 5. 构建RoundWorm
此步骤依赖build-essential (gcc, make...), libinotifytools
```shell
cd RoundWorm
make
```
构建成功后将得到
- roundworm

## 6. 构建Guardian
此步骤依赖build-essential (gcc, make...), PHP7-CLI和部分必要的PHP扩展
```shell
cd Guardian
php compile.php
```
构建成功后将得到
- guardian.phar

## 至此，AoiAWD项目所需组件均已构建完毕
