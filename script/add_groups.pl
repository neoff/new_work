#!/usr/bin/perl -w

##################################
#Добавление описаний товаров в БД#
##################################


use strict;
use DBI;#подключаем возможность работы с БД
use Encode qw(encode decode is_utf8 encode_utf8);
use HTML::Entities;
use Encode::Guess;
use utf8;
#Переменные для коннекта к БД
my $db_user = 'mvideo';
my $db_password = 'testtest';
my $db_host = 'localhost';
my $db_port = '';
my $db_name = 'mvideo';
my $db_options = '';
my $db_tty = '';
#----------------------------
my $path_to_data = 'D:/data_db/2/';#Путь к файлам с выгрузкой


my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#коннектимся к БД

$dbh->prepare("truncate kis_groups;")->execute();

my $count = 0;
my $fname = $path_to_data."groups";
open(FINP,"<$fname") or die("Failed to open $fname.\n");#открываем файл на чтение
while(<FINP>){
    $_ = encode("utf-8",decode("cp1251",$_));
    my @data = split ";", $_;
    my $l = @data;
    if($l == 0 || $data[0] !~ m/(\d+)/g || $data[0] eq '' || $data[0] == 0){
        next;
    }
    $dbh->prepare(sprintf("INSERT INTO kis_groups (group_id,group_name) VALUES ('%d','%s');",$data[0],encode_entities($data[1], '\'"')))->execute;
}
close FINP;
