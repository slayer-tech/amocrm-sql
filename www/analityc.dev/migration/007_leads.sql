create table if not exists `leads` (
`id` int(11) KEY,
`name` varchar(255) NOT NULL,
`price` int(11),
`responsible_user_id` int(11) NOT NULL,
`group_id` int(11) NOT NULL,
`status_id` int(11) NOT NULL,
`pipeline_id` int(11) NOT NULL,
`loss_reason_id` int(11),
`created_by` int(11) NOT NULL,
`updated_by` int(11) NOT NULL,
`closed_at` timestamp,
`created_at` timestamp NOT NULL,
`updated_at` timestamp,
`closest_task_at` timestamp,
`is_deleted` int(1))
engine = innodb
auto_increment = 1
character set utf8
collate utf8_general_ci;

