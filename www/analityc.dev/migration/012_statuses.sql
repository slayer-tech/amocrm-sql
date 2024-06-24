create table if not exists `statuses` (
`id` int(11) KEY,
`name` text,
`sort` int(11),
`is_editable` int(1),
`type` int(1),
`account_id` int(11)
)
engine = innodb
character set utf8
collate utf8_general_ci;