create table if not exists `pipelines` (
`id` int(11) KEY,
`name` varchar(255) NOT NULL,
`sort` int(11),
`is_main` int(1),
`is_unsorted_on` int(1),
`is_archive` int(1),
`account_id` int(11)
)
engine = innodb
character set utf8
collate utf8_general_ci;