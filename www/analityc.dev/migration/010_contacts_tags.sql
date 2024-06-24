create table if not exists `contacts_tags` (
`id_contact` int(11) NOT NULL,
`id` int(11) NOT NULL,
`name` text,
FOREIGN KEY (`id_contact`)  REFERENCES `contacts` (`id`)
)
engine = innodb
character set utf8
collate utf8_general_ci;