-- Таблица `events-data`

create table if not exists `events_data` (
  `id` int(11) PRIMARY KEY AUTO_INCREMENT,
  `date` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `entity` varchar(255) NOT NULL,
  `id_entity` int(11) NOT NULL,
  `json_data` text NOT NULL,
  `timestamp` int(11) NOT NULL
)
engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;

-- Таблица versions --
create table if not exists `versions` (
    `id` int(10) unsigned not null auto_increment,
    `name` varchar(255) not null,
    `created` timestamp default current_timestamp,
    primary key (id)
)
engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;