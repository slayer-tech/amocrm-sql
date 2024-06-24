create table if not exists `leads_fields` (
`id_lead` int(11) NOT NULL,
`name` varchar(255) NOT NULL,
`value` text,
`field_id` int(11),
FOREIGN KEY (`id_lead`)  REFERENCES `leads` (`id`)
)
engine = innodb
character set utf8
collate utf8_general_ci;