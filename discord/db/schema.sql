-- n.b.: this is designed for sqlite 3, but use it with whatever

CREATE TABLE invite_log (
	event_id integer PRIMARY KEY AUTOINCREMENT,
	reddit_username varchar(255) NOT NULL,
	invite_code varchar(10) NOT NULL,
	invite_time int unsigned NOT NULL,
	ip_address varchar(39) NOT NULL
);