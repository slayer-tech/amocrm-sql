create table if not exists `leads_tags` (
`id_lead` int(11) NOT NULL,
`id` int(11) NOT NULL,
`name` text,
FOREIGN KEY (`id_lead`)  REFERENCES `leads` (`id`)
)
engine = innodb
character set utf8
collate utf8_general_ci;