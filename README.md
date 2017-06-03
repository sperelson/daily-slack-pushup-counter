# Daily Slack Pushup Counter
A simple daily pushup counter that uses the Slack Webhook. Will give a 10 day rolling tally leaderboard of the top 5 participants for your team.

## Setup

You need a MySQL DB with the following table and primary key:

```
CREATE TABLE IF NOT EXISTS `pushups` (
  `today` date NOT NULL,
  `teamid` varchar(255) NOT NULL,
  `userid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `pushups`
  ADD PRIMARY KEY (`today`,`teamid`,`userid`);
```

Then rename `config-example.php` to `config.php` and set all the values.
To get the Slack Webhook token, create a new Slack Webhook integration for your Daily Pushup Counter and retrieve the token from there.
