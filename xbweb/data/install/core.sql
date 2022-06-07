create table `[+prefix+]users` (
    `id`        bigint not null auto_increment,
    `login`     char(32)  not null,
    `email`     char(128) null,
    `phone`     bigint null,
    `password`  char(64)  not null,
    `key`       char(32)  not null,
    `created`   datetime null,
    `activated` datetime null,
    `deleted`   datetime null,
    `role`      tinyint not null default '0',
    `flags`     int not null default '0',
    primary key (`id`),
    unique index (`login`)
) engine = InnoDB comment = 'Users';

insert into `[+prefix+]users`
    (`login`, `email`, `password`, `key`, `created`, `activated`, `role`)
values
    ('admin', 'your@email', md5('password'), '0123456789abcdef0123456789abcdef', now(), now(), -1)