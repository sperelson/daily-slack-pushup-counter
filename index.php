<?php
error_reporting(0);

/*
    MIT License

    Copyright (c) 2017 Stephen Perelson

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
*/

class Pushups
{
	private $mycon;

	public function __construct() {
		require 'config.php';
		$response = '';

		$method = isset($_SERVER["REQUEST_METHOD"]) && $_POST ? strtoupper($_SERVER["REQUEST_METHOD"]) : 'GET';

		switch ($method) {
			case 'GET':
				$response = 'Daily Pushups Counter for Slack Teams<br />Brought to you with minor affection by <a href="https://twitter.com/perelson">@perelson</a>';
				break;
			case 'POST':
        		header('Content-type: application/json');
                $data = $_POST;
                /*
                    Possible Webhook POST data
                    --------------------------
                    token
                    team_id
                    team_domain
                    enterprise_id
                    enterprise_name
                    channel_id
                    channel_name
                    user_id
                    user_name
                    command
                    text
                    response_url
                */
                $token = $this->arrayGet($data, 'token');
                $teamId = $this->arrayGet($data, 'team_id', false);
                $userId = $this->arrayGet($data, 'user_id', false);
                $username = $this->arrayGet($data, 'user_name', '');
                $value = $this->arrayGet($data, 'text');
                $responseUrl = $this->arrayGet($data, 'response_url', false);

                if (
                    $token == $this->slackToken
                    && $teamId !== false
                    && $userId !== false
                    && $responseUrl !== false
                ) {
                    $con = $this->dbCon();

                    if (is_numeric($value)) {
                        $sql = '
                            insert into pushups (`today`, `teamid`, `userid`, `name`, `count`)
                            values (NOW(), ?, ?, ?, ?)
                            on duplicate key update `count` = `count` + ?
                        ';
			            $stmt = $con->prepare($sql);
                        $stmt->bind_param("sssii", $teamId, $userId, $username, $value, $value);
                        $stmt->execute();
                        $stmt->close();
                        $response = 'Nice. I have added ' . $value . ' to your daily pushup count';
                    }
                    $resSql = '
                        select SUM(count) as counts, name from pushups
                        where today > DATE_SUB(NOW(), INTERVAL 31 DAY)
                        group by userid
                        order by counts desc
                        limit 5
                    ';
                    $stmt = $con->prepare($resSql);
                    $stmt->execute();
        			$stmt->bind_result($count, $name);
        			$leaders = '';
        			$position = 0;

                    while ($stmt->fetch()) {
                        $position++;
                        $leaders .= $position . '. ' . $name . ' | ' . $count;
                        $leaders .= "\n";
                    }
                    $stmt->close();

                    if (strlen($leaders) > 0) {
                        $response .= "\n";
                        $response .= '*10 day leaderboard (top 5)*';
                        $response .= "\n";
                        $response .= $leaders;
                    }
                }
				break;
		}

        echo $response;
	}

	private function dbCon() {
		if (!isset($this->mycon)) {
			$this->mycon = new mysqli($this->mysql['server'], $this->mysql['user'], $this->mysql['pwd'], $this->mysql['db']);
		}
		if (mysqli_connect_errno()) {
			echo '';
			die();
		}
		return $this->mycon;
	}

    private function arrayGet($array, $key, $default = null)
    {
        if (!is_array($array)) {
            return $default;
        }

        if (is_null($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $array;
    }
}

$pushups = new Pushups();
