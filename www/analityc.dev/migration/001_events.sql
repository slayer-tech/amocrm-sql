create table if not exists `events_data` (
`id` varchar(255) NOT NULL PRIMARY KEY,
`date` varchar(255) NOT NULL,
`type` varchar(255) NOT NULL,
`entity` varchar(255) NOT NULL,
`id_entity` int(11) NOT NULL,
`timestamp` int(11) NOT NULL
)
engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;