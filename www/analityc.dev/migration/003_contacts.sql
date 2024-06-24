create table if not exists `contacts` (
`id` int(11) PRIMARY KEY,
`crm_id` int(11) NOT NULL,
`name` varchar(255) NULL,
`first_name` varchar(255),
`last_name` varchar(255),
`responsible_user_id` int(11) NOT NULL,
`group_id` int(11) NOT NULL,
`created_by` int(11) NOT NULL,
`updated_by` int(11) NOT NULL,
`created_at` timestamp NOT NULL,
`updated_at` timestamp)
engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;

