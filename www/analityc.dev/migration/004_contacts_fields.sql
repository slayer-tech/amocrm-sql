create table if not exists `contacts_fields` (
`id_contact` int(11) NOT NULL,
`name` varchar(255) NOT NULL,
`value` text,
FOREIGN KEY (`id_contact`)  REFERENCES `contacts` (`id`)
)
engine = innodb
character set utf8
collate utf8_general_ci;