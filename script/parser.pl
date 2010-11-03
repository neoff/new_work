#!c:/perl/bin/perl -w
#use warnings;

#####################################################################################
#���������� � ��: �������, ��������, �������, �����, ��������������, ���, ����������#
#####################################################################################


use strict;
use DBI;#���������� ����������� ������ � ��
use threads ('yield',
#'stack_size' => 4096,
'exit' => 'threads_only',
'stringify');#���������� ���������������
use threads::shared;#���������� ����������� ������������� ����� ��������


use Encode qw(encode decode is_utf8 encode_utf8);#���������� ��������� ������������� � utf-8
use HTML::Entities;#��� ���������� ��������� ��������
use Encode::Guess;#��� ���� ��� �� "���������" ��������� ������
use utf8;#���������� ���������� ��������� utf-8


#���������� �������

my $path_to_data = 'D:/data_db/4/';#���� � ������ � ���������

#���������� ��� �������� � ��
my $db_user = 'mvideo';
my $db_password = 'testtest';
my $db_host = 'localhost';
my $db_port = '';
my $db_name = 'mvideo';
my $db_options = '';
my $db_tty = '';
#----------------------------

my @buffer : shared;#����� ��� �������� ������ ����� ��������
my @file_list : shared;#������ ������
my $MAX_BUFFER_SIZE : shared = 400000;#������������ ������ ������ � ���������� �������
my $MAX_READ_LINES : shared = 500;#������� ������ �� ��� �����
my $MAX_THREADS = 2;#����� ��� �� ������������
my $sig_to_die : shared = 0;#������ ��� ������ ��������, ��� ����� �������
my $current_file = '';# ������� ���� ��� ���������
my $allow_die : shared = 0;#���������� ������� �� ������������ ������


#---------------------------------------

my $total_read_warez : shared = 0;#��������
my $total_read_qty : shared = 0;  #

#-----------------------------
my @tmp_qty;#������ ������ qty*
my @tmp_warez;#������ ������ warez*
my @my_threads : shared;#���������� ���������� �������
my %warecodes : shared;#��� ����� ������� ����������� warecode ��� �� ������ ��� �� ���������� � ��
my %properties : shared;# {property_name} = property_id
my %property_groups: shared;# {property_group_name} = property_group_id
my $current_desc_id : shared = 1;#������� �������� desc_id, ��� �� ������ �� �������������
my $current_property_id : shared  = 1;#������� �������� property_name_id
my $current_property_group_id : shared  = 1;#������� �������� property_group_id



#��� ��� ������� ������ ��� ������, ��� �� ����� � ������� �� csv �� ��������

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



sub read_files(){#������� ��� ������ �����
    sub close_reads(){#���������� ��������� ������
        {
            lock @my_threads;
            shift @my_threads;
        }
        #if($allow_die != 0){
        {
            lock $allow_die;
            $allow_die = 1;#�������� ���������� ��� ������ ������ ��������� ������, ��-����� ��� �������
                #�������� insert_db ��������� ���� ������ ��� ����� ��������
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
            $sname = shift @file_list;#������ ��� �����        
            $fname = shift @file_list;#������ ���� �� �����
            $file_type = shift @file_list;;#warez ��� qty ��� descr;
        }
        
        print "File $sname started.\n";
        my $region = 0;
        $sname =~ m/(\d+)/ig;#�������� ������ �� �������� �����
        if(!$1&&$file_type ne "desc"){#���� ��� �������, �� ��������� �������� - ���� ��� �� �����
            #print "File $sname ended with empty result.\n";
            next;
            #close_read();
            #return;
        }
        $region = $1 || -1;#���� ���� ������, ���������� ���
        open(FINP,"<$fname") or die("Failed to open $fname.\n");#��������� ���� �� ������
        my $count = 0;
        my @inner_buffer;
        while(<FINP>){#������ ���������
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
                $_ .= $region;#��������� ����� �������, �.�. ������ �� ����� �� ���������� ������
                $_ .= ";";
                $_ .= $file_type;
                #print $_."\n";
            }
            
            $_ = encode("utf-8",decode("cp1251",$_));
            push(@inner_buffer,$_);
            $count++;
            if($count==$MAX_READ_LINES){
                lock(@buffer);#�������� �����, ��� �� �� ���� ��������� ��������� � ����������
                while(@inner_buffer){
                    push(@buffer,pop(@inner_buffer));#��������� ������ � �����
                };
                $count = 0;
            }
            while(@buffer>$MAX_BUFFER_SIZE){#��� ���� ������ �������� ������� ����� �������, ��� �� �� �������� ������ ������
                threads->yield();
                sleep 1;
            }
            continue{
                while(@buffer>$MAX_BUFFER_SIZE*0.5){#��� ������ ������������ �� 1/2 � ��� �� �� ����������
                    threads->yield();
                    sleep 3;
                }
                my $tmp = @buffer;
                print "\nbuffsize now: $tmp\n";
            };
            
        }
        close FINP;
        {
            lock(@buffer);#�������� �����, ��� �� �� ���� ��������� ��������� � ����������
            while(@inner_buffer!=0){
                push @buffer, pop @inner_buffer;#��������� ������ � �����
            };
        }
        print "File $sname complite in ".(time-$time).".\n";
        undef $count;
        undef $fname;
        undef $sname;
        undef $file_type;
        undef @inner_buffer;
    }
    close_reads();#��������� �����
    return;
}


sub insert_db {#������� ��� ������� ������ � ��
    my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#����������� � ��
    my $h = $dbh->prepare("truncate kis_catalog_index;truncate kis_catalog;truncate kis_catalog_relations;truncate kis_prices;");#������� ������ �������
    $h->execute();
    $dbh->prepare("set enable_seqscan = off;")->execute();
    my $warez_idx = 1;
    
    #������� ������ � ������� kis_catalog_index
    my $query_ins_ki = "INSERT INTO kis_catalog_index (catalog_id,dir_id,class_id,group_id) VALUES ('%d','%d','%d','%d');";
    
    #������� ������ � ������� kis_catalod
    my $query_ins_kc = "INSERT INTO kis_catalog (goods_id,full_name,descr,short_name,mark_id) VALUES ('%d','%s','%s','%s','%d');";

    #������� ���
    my $query_ins_kp = "INSERT INTO kis_prices (goods_id,old_price,price,region_id) VALUES ('%d','%d','%d','%d');";
    
    #my $query_sel_gid = "SELECT goods_id FROM kis_catalog_relations WHERE catalog_id = '%d';";
    
    #���������� ���������� ������
    my $query_upd_qty = "UPDATE kis_prices SET quantity = '%d' WHERE goods_id = '%d' AND region_id = '%d';";
    
    
    my $cnt = 0;
    my $cnt1 = 0;
    $| = 1;
    while(){#�������� ����
        if(@buffer==0&&$allow_die==1){#��������, �� ���� �� ������ �������
            {
                lock @my_threads;
                shift @my_threads;
            }
            $dbh->disconnect();
            threads->exit();
        }
        my $record = '';
        {#����������� ������ �� ������
            lock(@buffer);
            $record = shift @buffer;
        }
        if(!defined $record || $record eq ''){#���� ������ ������ ��� �� ����������, �� ��� �� ��������� ����
            next;
        };
        my @data = split(";",$record);#��������� csv
        my $type = pop @data;#��� ������ �� ������ �������� ��� ������, ����� ����� ��� ������� ������
        if($type eq 'warez'){#���� ��� �������� �������
            my $region = pop @data;
            $cnt++;
            if($cnt==3000){# �� ������� ������ ������
                print "Processed another $cnt records\n";
                $cnt = 0;
            }
            
            my $warecode = @data[$warez_csv{warecode}];#���� ��� ������
            my $l = @data;
            if($l == 0 || $warecode !~ m/(\d+)/g || $warecode eq '' || $warecode == 0){#��������� �� ����� �� ������
                next;
            }
            $| = 1;
            if(exists $warecodes{$warecode} && defined $warecodes{$warecode} && $warecodes{$warecode} != 0 && $warecodes{$warecode} ne ''){#���������, �� ��������� �� �� ����� ����� ������
                my $r = $dbh->prepare(sprintf($query_ins_kp,$warecodes{$warecode},$data[$warez_csv{old_price}] || 0,$data[$warez_csv{price}]|| 0,$region));#��������� ���� � ��, ��� ������� ������
                $r->execute;
                next;#��� �� ��������� ����
            }
            $warez_idx = $warecode;#������ ��� ��� ������ ����� ����� �� ��� � ���
            
            #��������� ������ � ������
            my $r = $dbh->prepare(sprintf($query_ins_ki,$data[$warez_csv{warecode}],$data[$warez_csv{dir_id}],$data[$warez_csv{class_id}],$data[$warez_csv{group_id}]).
                               sprintf($query_ins_kc,$warez_idx,encode_entities($data[$warez_csv{full_name}], '\'"'),encode_entities($data[$warez_csv{descr}], '\'"'),encode_entities($data[$warez_csv{short_name}], '\'"'),$data[$warez_csv{mark_id}]));
            $r->execute();
            {#��������� � ��� ������ � ��� ��� ����� � ����� ����� ��� ���� � ��
                lock %warecodes;
                $warecodes{$warecode} = $warez_idx;
            }
            
            #��������� ���� ������
            $r = $dbh->prepare(sprintf($query_ins_kp,$warecode,$data[$warez_csv{old_price}] || 0,$data[$warez_csv{price}]|| 0,$region));
            $r->execute;
        } elsif($type eq 'qty') {#���� ��� ������ ������� � ����������� ������
            my $region = pop @data;
            my $warecode = @data[$qty_csv{warecode}];#���� ��� ������
            my $l = @data;
            if(!defined $warecode || $l == 0 || $warecode !~ m/(\d+)/g  || $warecode eq ''){#���������, �������� �� ��� ������(��� �����)
                next;
            }
            if(!exists $warecodes{$warecode} || $warecodes{$warecode} eq ''){#���������, �������� �� ����� � ��, ���� ���, �� ������� ������ �����, � ����� ������� � ��� �� ����� ����
                {
                    lock @buffer;
                    push @buffer, join(";",@data);
                }
                next;
            }
            my $r = $dbh->prepare(sprintf($query_upd_qty,$data[$qty_csv{shop_qty}],$warecodes{$warecode},$region));#��������� ���������� ������
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


sub add_refs(){#��� �� ���� ������ ������ �����������. ����� ������� ��� ��� �� ��������
    my $dbh = DBI->connect("dbi:PgPP:dbname=$db_name;host=$db_host;port=$db_port;options=$db_options;tty=$db_tty","$db_user","$db_password",
		    {PrintError => 1}) or exit(0);#����������� � ��

    $dbh->prepare("truncate kis_dirs;truncate kis_classes;truncate kis_marks;")->execute;
    $| = 1;
    #����� ��������� ������
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
    #��������� ��������������
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
    #��������� ����������
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
		    {PrintError => 1}) or exit(0);#����������� � ��
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
threads->create(\&insert_db);#������ �����, ������� ����� ���������� ��������� � ��
push @my_threads,1;

threads->create(\&insert_db);#������ ������ �����, ������� ����� ���������� ��������� � ��, ����� � ��� ����, �� ����� ������ � �� ����� ���������
push @my_threads,1;

opendir(DR,$path_to_data) || die("Can`t open directory.\n");#��������� ����������

 
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

threads->create(\&read_files);#������ �����, ������� ����� ���������� �������
push @my_threads,1;

while(@my_threads>2){#������� ���������� ������
    threads->yield();
    sleep 1;
};



while($file = shift(@tmp_qty)){
    push @file_list, $file;
    push @file_list, $path_to_data.$file;
    push @file_list, 'qty';
}

threads->create(\&read_files);#������ �����, ������� ����� ���������� ��������� � ��
push @my_threads,1;
threads->yield();

#�� ���� �������, ����� ����� ���������, �� ������ ��� ����� ���� ���������
while(@my_threads>2){#��-����� ������ �� ����������
     threads->yield();
     $| = 1;
     sleep 1;
};

{
    lock $allow_die;
    $allow_die = 1;
}

#����� ��� ����� �������

print @my_threads;
while(@my_threads>0){
     threads->yield();
     sleep 1;
}


push @file_list, "desclist";
push @file_list, $path_to_data."desclist";
push @file_list, "desc";

$allow_die = 0;

threads->create(\&read_files);#������ �����, ������� ����� ���������� �������
push @my_threads, 1;

threads->create(\&insert_descriptions);#����� ���������� � ��
push @my_threads, 1;

threads->create(\&insert_descriptions);#��� ����� ���������� � ��
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
