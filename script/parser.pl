#!c:/perl/bin/perl -w
#use warnings;

#####################################################################################
#Добавление в БД: товаров, разделов, классов, групп, производителей, цен, количества#
#####################################################################################


use strict;
use DBI;#подключаем возможность работы с БД
use threads ('yield',
#'stack_size' => 4096,
'exit' => 'threads_only',
'stringify');#Подключаем многопоточность
use threads::shared;#Подключаем возможность использования общих ресурсов


use Encode qw(encode decode is_utf8 encode_utf8);#Подключаем поддержку перекодировки в utf-8
use HTML::Entities;#для подсекания неугодных символов
use Encode::Guess;#Для того что бы "угадывать" кодировку текста
use utf8;#Подключаем собственно поддержку utf-8


#Переменные парсера

my $path_to_data = 'D:/data_db/4/';#Путь к файлам с выгрузкой

#Переменные для коннекта к БД
my $db_user = 'mvideo';
my $db_password = 'testtest';
my $db_host = 'localhost';
my $db_port = '';
my $db_name = 'mvideo';
my $db_options = '';
my $db_tty = '';
#----------------------------

my @buffer : shared;#Буфер для передачи данных между потоками
my @file_list : shared;#Список файлов
my $MAX_BUFFER_SIZE : shared = 400000;#Максимальный размер буфера в количестве записей
my $MAX_READ_LINES : shared = 500;#Сколько читаем за раз строк
my $MAX_THREADS = 2;#вроде как не используется
my $sig_to_die : shared = 0;#сигнал для потока загрузки, что можно умереть
my $current_file = '';# текущий файл для обработки
my $allow_die : shared = 0;#разрешение умереть от управляющего потока


#---------------------------------------

my $total_read_warez : shared = 0;#Счётчики
my $total_read_qty : shared = 0;  #

#-----------------------------
my @tmp_qty;#список файлов qty*
my @tmp_warez;#список файлов warez*
my @my_threads : shared;#количество запущенных потоков
my %warecodes : shared;#тут будем хранить добавленные warecode что бы лишний раз не обращаться к БД
my %properties : shared;# {property_name} = property_id
my %property_groups: shared;# {property_group_name} = property_group_id
my $current_desc_id : shared = 1;#текущее значение desc_id, что бы потоки не конфликтовали
my $current_property_id : shared  = 1;#текущее значение property_name_id
my $current_property_group_id : shared  = 1;#текущее значение property_group_id



#Это так сказать индекс для файлов, что бы потом с цифрами из csv не мучаться

my %warez_csv : shared =  (
    'warecode', 1,
    'dir_id'  , 2,
    'class_id' , 3,
    'group_id' , 4,
    'mark_id'  , 5,
    'descr'    , 19,
    'full_name' , 7,
    'short_name' , 6,
    'price' , 8,
    'old_price' ,26,
    'region' , 28
);


my %qty_csv : shared = (
    'warecode' , 0,
    'inet_qty' , 1,
    'shop_qty' , 2,
    'region' , 3
);

my %desclist : shared = (
    'warecode', 0,
    'property_code', 1,
    'property_order', 2,
    'property_name', 3,
    'property_value', 4,
    'property_valuenum', 5,
    'property_group', 6,
    'short_descr', 7,
    'search', 8
);

#----------------------------------------



sub read_files(){#функция для чтения файла
    sub close_reads(){#Определяем процедуру выхода
        {
            lock @my_threads;
            shift @my_threads;
        }
        #if($allow_die != 0){
        {
            lock $allow_die;
            $allow_die = 1;#Процессы отработали или должны вскоре завершить работу, по-этому даём команду
                #процессу insert_db завершить свою работу как буфер опустеет
        }
        #}
        print "Read thread is ended\n";            
        threads->exit();
        
    }
    my $sname;
    my $fname;
    my $file_type;
    $| = 1;
    while(@file_list!=0){
        #print join "\n",@file_list;
        if(@file_list==0){
            close_reads();
            return;
        }
        my $time = time;
        {
            lock(@file_list);
            $sname = shift @file_list;#только имя файла        
            $fname = shift @file_list;#Полный путь до файла
            $file_type = shift @file_list;;#warez или qty или descr;
        }
        
        print "File $sname started.\n";
        my $region = 0;
        $sname =~ m/(\d+)/ig;#выдираем регион из названия файла
        if(!$1&&$file_type ne "desc"){#если нет региона, то завершаем итерацию - файл нам не нужен
            #print "File $sname ended with empty result.\n";
            next;
            #close_read();
            #return;
        }
        $region = $1 || -1;#если есть регион, запоминаем его
        open(FINP,"<$fname") or die("Failed to open $fname.\n");#открываем файл на чтение
        my $count = 0;
        my @inner_buffer;
        while(<FINP>){#читаем построчно
            if($file_type eq 'desc'){
                my @tmp = split ";",$_;
                if(!exists $warecodes{$tmp[$desclist{warecode}]} || $warecodes{$tmp[$desclist{warecode}]} eq ''){
                    next;
                }
            }
            if($file_type eq 'warez'){
                $total_read_warez++;
            } elsif ($file_type eq 'qty') {
                $total_read_qty++;
            }
            $_ =~ s/\n|\r//;
            if($region != -1){
                $_ .= ";";
                $_ .= $region;#Добавляем номер региона, т.к. читать мы можем из нескольких файлов
                $_ .= ";";
                $_ .= $file_type;
                #print $_."\n";
            }
            
            $_ = encode("utf-8",decode("cp1251",$_));
            push(@inner_buffer,$_);
            $count++;
            if($count==$MAX_READ_LINES){
                lock(@buffer);#Запираем буфер, что бы не было конфликта обращения к переменной
                while(@inner_buffer){
                    push(@buffer,pop(@inner_buffer));#добавляем запись в буфер
                };
                $count = 0;
            }
            while(@buffer>$MAX_BUFFER_SIZE){#Ждём если буффер содержит слишком много записей, что бы не занимать лишнюю память
                threads->yield();
                sleep 1;
            }
            continue{
                while(@buffer>$MAX_BUFFER_SIZE*0.5){#Даём буферу освободиться на 1/2 и что бы БД поработала
                    threads->yield();
                    sleep 3;
                }
                my $tmp = @buffer;
                print "\nbuffsize now: $tmp\n";
            };
            
        }
        close FINP;
        {
            lock(@buffer);#Запираем буфер, что бы не было конфликта обращения к переменной
            while(@inner_buffer!=0){
                push @buffer, pop @inner_buffer;#добавляем запись в буфер
            };
        }
        print "File $sname complite in ".(time-$time).".\n";
        undef $count;
        undef $fname;
        undef $sname;
        undef $file_type;
        undef @inner_buffer;
    }
    close_reads();#Завершаем поток
    return;
}


sub insert_db {#функция для вставки данных в БД
    my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#коннектимся к БД
    my $h = $dbh->prepare("truncate kis_catalog_index;truncate kis_catalog;truncate kis_catalog_relations;truncate kis_prices;");#очищаем нужные таблицы
    $h->execute();
    $dbh->prepare("set enable_seqscan = off;")->execute();
    my $warez_idx = 1;
    
    #вставка данных в таблицу kis_catalog_index
    my $query_ins_ki = "INSERT INTO kis_catalog_index (catalog_id,dir_id,class_id,group_id) VALUES ('%d','%d','%d','%d');";
    
    #вставка данных в таблицу kis_catalod
    my $query_ins_kc = "INSERT INTO kis_catalog (goods_id,full_name,descr,short_name,mark_id) VALUES ('%d','%s','%s','%s','%d');";

    #вставка цен
    my $query_ins_kp = "INSERT INTO kis_prices (goods_id,old_price,price,region_id) VALUES ('%d','%d','%d','%d');";
    
    #my $query_sel_gid = "SELECT goods_id FROM kis_catalog_relations WHERE catalog_id = '%d';";
    
    #обновление количества товара
    my $query_upd_qty = "UPDATE kis_prices SET quantity = '%d' WHERE goods_id = '%d' AND region_id = '%d';";
    
    
    my $cnt = 0;
    my $cnt1 = 0;
    $| = 1;
    while(){#основной цикл
        if(@buffer==0&&$allow_die==1){#проверка, не пора ли потоку умереть
            {
                lock @my_threads;
                shift @my_threads;
            }
            $dbh->disconnect();
            threads->exit();
        }
        my $record = '';
        {#вытаскиваем запись из буфера
            lock(@buffer);
            $record = shift @buffer;
        }
        if(!defined $record || $record eq ''){#если запись пустая или не определена, то идём на следующий цикл
            next;
        };
        my @data = split(";",$record);#разбиваем csv
        my $type = pop @data;#при чтении мы вконец записали тип записи, самое время его забрать оттуда
        if($type eq 'warez'){#если это является товаром
            my $region = pop @data;
            $cnt++;
            if($cnt==3000){# на рабочей версии убрать
                print "Processed another $cnt records\n";
                $cnt = 0;
            }
            
            my $warecode = @data[$warez_csv{warecode}];#берём код товара
            my $l = @data;
            if($l == 0 || $warecode !~ m/(\d+)/g || $warecode eq '' || $warecode == 0){#проверяем не пуста ли запись
                next;
            }
            $| = 1;
            if(exists $warecodes{$warecode} && defined $warecodes{$warecode} && $warecodes{$warecode} != 0 && $warecodes{$warecode} ne ''){#проверяем, не добавляли ли мы такой товар раньше
                my $r = $dbh->prepare(sprintf($query_ins_kp,$warecodes{$warecode},$data[$warez_csv{old_price}] || 0,$data[$warez_csv{price}]|| 0,$region));#добавляем цену в БД, для региона записи
                $r->execute;
                next;#идём на следующий круг
            }
            $warez_idx = $warecode;#решили что код товара будет такой же как в КИС
            
            #вставляем данные о товаре
            my $r = $dbh->prepare(sprintf($query_ins_ki,$data[$warez_csv{warecode}],$data[$warez_csv{dir_id}],$data[$warez_csv{class_id}],$data[$warez_csv{group_id}]).
                               sprintf($query_ins_kc,$warez_idx,encode_entities($data[$warez_csv{full_name}], '\'"'),encode_entities($data[$warez_csv{descr}], '\'"'),encode_entities($data[$warez_csv{short_name}], '\'"'),$data[$warez_csv{mark_id}]));
            $r->execute();
            {#добавляем в хеш запись о том что товар с таких кодом уже есть в БД
                lock %warecodes;
                $warecodes{$warecode} = $warez_idx;
            }
            
            #вставляем цену товара
            $r = $dbh->prepare(sprintf($query_ins_kp,$warecode,$data[$warez_csv{old_price}] || 0,$data[$warez_csv{price}]|| 0,$region));
            $r->execute;
        } elsif($type eq 'qty') {#если это запись таблицы с количеством товара
            my $region = pop @data;
            my $warecode = @data[$qty_csv{warecode}];#берём код товара
            my $l = @data;
            if(!defined $warecode || $l == 0 || $warecode !~ m/(\d+)/g  || $warecode eq ''){#проверяем, определён ли код товара(это важно)
                next;
            }
            if(!exists $warecodes{$warecode} || $warecodes{$warecode} eq ''){#проверяем, добавлен ли товар в БД, если нет, то заносим запись назад, в конец очереди и идём на новый круг
                {
                    lock @buffer;
                    push @buffer, join(";",@data);
                }
                next;
            }
            my $r = $dbh->prepare(sprintf($query_upd_qty,$data[$qty_csv{shop_qty}],$warecodes{$warecode},$region));#обновляем количество товара
            $r->execute();
            $cnt1++;
            if($cnt1==3000){
                my $tmp_cnt = @buffer;
                print "Processed another 3000, buffer size = $tmp_cnt\n";
                $cnt1 = 0;
            }
        }
    }  
    {
        lock @my_threads;
        shift @my_threads;
        $dbh->disconnect();
        threads->exit();
    }
    
}


sub add_refs(){#как же меня ломает писать комментарии. Будем считать что тут всё очевидно
    my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#коннектимся к БД

    $dbh->prepare("truncate kis_dirs;truncate kis_classes;truncate kis_marks;")->execute;
    $| = 1;
    #Будем добавлять классы
    my $filename = $path_to_data."classes";
    open(FCLASS,"<$filename");
    my $data = <FCLASS>;#Skip first line;
    while($data = <FCLASS>){
        my @row = split ";",$data;#encode_utf8($data);
        if(@row == 2){
            $dbh->prepare(sprintf("INSERT INTO kis_classes (class_id,class_name) VALUES ('%d','%s');",$row[0],encode("utf-8",decode("cp1251",encode_entities($row[1], '\'"')))))->execute;
        }
    }
    close FCLASS;
    #Добавляем производителей
    $filename = $path_to_data."marks";
    open(FMARKS, "<$filename");
    $data = <FMARKS>;
    while($data = <FMARKS>){
        my @row = split ";", $data; #encode_utf8($data);
        if(@row == 3){
            $dbh->prepare(sprintf("INSERT INTO kis_marks (mark_id,mark_name) VALUES ('%d','%s');",$row[0],encode("utf-8",decode("cp1251",encode_entities($row[1], '\'"')))))->execute;
        }
    }
    close FMARKS;
    #Добавляем директории
    $filename = $path_to_data."dirs";
    open(FDIRS, "<$filename");
    $data = <FDIRS>;
    while($data = <FDIRS>){
        my @row = split ";", $data;#encode_utf8($data);
        if(@row == 2){
            $dbh->prepare(sprintf("INSERT INTO kis_dirs (dir_id,dir_name) VALUES ('%d','%s');",$row[0],encode("utf-8",decode("cp1251",encode_entities($row[1], '\'"')))))->execute;
        }
    }
    close FDIRS;
    {
        lock @my_threads;
        shift @my_threads;
        $dbh->disconnect();
        threads->exit();
    }
    
}


sub insert_descriptions(){
    my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#коннектимся к БД
    my $h = $dbh->prepare("truncate kis_desclist; truncate kis_desclist_index;truncate kis_properties; truncate kis_property_groups;");
    $h->execute();
    $dbh->prepare("set enable_seqscan = off;")->execute();
    $| = 1;
    
    
    
    #SQLS
    my $query_ins_property = "INSERT INTO kis_properties (property_name_id,property_name) VALUES ('%d','%s');";
    my $query_ins_property_group = "INSERT INTO kis_property_groups (property_group_id,property_group_name) VALUES ('%d','%s');";
    my $query_ins_desc_index = "INSERT INTO kis_desclist_index (desc_id,property_name_id,property_group_id,goods_id,desc_order,desc_value_mixed,desc_short_descr,desc_search) VALUES ('%d','%d','%d','%d','%d','%s','%s','%d');";
    #/SQLS
    
    while(){
        if(@buffer==0&&$allow_die==1){
            {
                print @buffer." - allow die =".$allow_die;
                lock @my_threads;
                shift @my_threads;
            }
            $dbh->disconnect();
            threads->exit();
        }
        my $record = '';
        {
            lock(@buffer);
            $record = shift @buffer;
        }
        if(!defined $record || $record eq ''){
            next;
        };
        my @data = split(";",$record);
        my $type = pop @data;
        my $warecode = $data[$desclist{warecode}];
        my $l = @data;
        if($l == 0 || $warecode !~ m/(\d+)/g || $warecode eq ''){
            next;
        }
        $| = 1;
        if(exists $warecodes{$warecode} && $warecodes{$warecode} != 0 && $warecodes{$warecode} ne ''){
            my $property_id = 0;
            my $property_group_id = 0;
            if(defined $properties{$data[$desclist{'property_name'}]}){
                lock %properties;
                $property_id = $properties{$data[$desclist{'property_name'}]};
            } else {
                lock %properties;
                {
                    lock $current_property_group_id;
                    $property_id = $current_property_id++;
                }
                $properties{$data[$desclist{'property_name'}]} = $property_id;
                $dbh->prepare(sprintf($query_ins_property,$property_id,encode_entities($data[$desclist{'property_name'}], '\'"')))->execute();
            }
            if(defined $property_groups{$data[$desclist{'property_group'}]}){
                lock %property_groups;
                $property_group_id = $property_groups{$data[$desclist{'property_group'}]};
            } else {
                lock %property_groups;
                {
                    lock $current_property_group_id;
                    $property_group_id = $current_property_group_id++;
                }
                $property_groups{$data[$desclist{'property_group'}]} = $property_group_id;
                $dbh->prepare(sprintf($query_ins_property_group,$property_group_id,encode_entities($data[$desclist{'property_group'}], '\'"')))->execute();
            }
            if($property_id != 0 && $property_group_id != 0){
                {
                    lock $current_desc_id;
                    $dbh->prepare(sprintf($query_ins_desc_index,$current_desc_id,$property_id,$property_group_id,$warecodes{$warecode},$data[$desclist{property_order}],encode_entities($data[$desclist{property_value}], '\'"') || '' ,encode_entities($data[$desclist{short_descr}], '\'"') || '0',$data[$desclist{search}] || 0))->execute();
                    $current_desc_id++;
                    $property_id = $property_group_id = 0;
                }
            }
        }
        else{
            #{
            #    lock @buffer;
            #    my $tmp = @buffer;
            #    print $tmp;
            #}
            #print "Possible missing good - Warecode = $warecode\n";
        }
    }  
    {
        {
            lock @my_threads;
            shift @my_threads;
        }
        $dbh->disconnect();
        threads->exit();
    }

}



#jedy code on
threads->create(\&add_refs);
push @my_threads,1;
while(@my_threads>0){
    threads->yield();
    sleep 1;
}

my $f_time = time;
threads->create(\&insert_db);#Создаём поток, который будет заниматься загрузкой в БД
push @my_threads,1;

threads->create(\&insert_db);#Создаём второй поток, который будет заниматься загрузкой в БД, можно и без него, но тогда запись в БД будет медленней
push @my_threads,1;

opendir(DR,$path_to_data) || die("Can`t open directory.\n");#открываем директорию

 
my $file;
while($file = readdir(DR)){
    if($file =~ m/qty/ig){
        push(@tmp_qty,$file);
        next;
    };
    if($file =~ m/warez_/ig){
        push(@tmp_warez,$file);
        next;
    }
};

my @thr_list;

while($file = shift(@tmp_warez)){
    push @file_list, $file;
    push @file_list, $path_to_data.$file;
    push @file_list, 'warez';
}

threads->create(\&read_files);#Создаём поток, который будет заниматься чтением
push @my_threads,1;

while(@my_threads>2){#Ожидаем завершения чтения
    threads->yield();
    sleep 1;
};



while($file = shift(@tmp_qty)){
    push @file_list, $file;
    push @file_list, $path_to_data.$file;
    push @file_list, 'qty';
}

threads->create(\&read_files);#Создаём поток, который будет заниматься загрузкой в БД
push @my_threads,1;
threads->yield();

#До сюда доходит, когда файлы кончились, но потоки ещё могут быть активными
while(@my_threads>2){#по-этому ожидаём их завершения
     threads->yield();
     $| = 1;
     sleep 1;
};

{
    lock $allow_die;
    $allow_die = 1;
}

#Далее идёт тёмная сторона

print @my_threads;
while(@my_threads>0){
     threads->yield();
     sleep 1;
}


push @file_list, "desclist";
push @file_list, $path_to_data."desclist";
push @file_list, "desc";

$allow_die = 0;

threads->create(\&read_files);#Создаём поток, который будет заниматься чтением
push @my_threads, 1;

threads->create(\&insert_descriptions);#Поток добавления в БД
push @my_threads, 1;

threads->create(\&insert_descriptions);#Ещё поток добавления в БД
push @my_threads, 1;
my $cnt = 1;

while(@my_threads>0){
    threads->yield();
    sleep 1;
    $cnt++;
    if($cnt%5 == 0){
        lock @my_threads;
        print "@my_threads \n";
        #$cnt = 0;
        #if(@my_threads==0 && @buffer == 0){
        #    print "Full time :".(time - $f_time)."\n";
        #    exit(0)
        #}
    }
    if($cnt%5000 == 0){
        lock @buffer;
        print join "\n", @buffer
    }
    #if(!$allow_die){
    #    lock $allow_die;
    #    $allow_die=1;
    #}
}


print "Full time :".(time - $f_time)."\n";
print "Buffer size: ".@buffer."\n";
exit(0);
#jedy code off
