<?php
session_start();

// Redis 연결 함수
function redisConnect() {
    try {
        $redis = new Redis();
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $redis->connect('tls://aws-redis-ydw-nzic8f.serverless.apse2.cache.amazonaws.com', 6379, 10, NULL, 0, 0, ['stream_context' => $context]);
        return $redis;
    } catch (Exception $e) {
        die('Redis connection failed: ' . $e->getMessage());
    }
}

// 세션 저장 핸들러 설정
$redis = redisConnect();
session_set_save_handler(
    function($path, $name) use ($redis) { return true; }, // open
    function() use ($redis) { return true; }, // close
    function($id) use ($redis) { return $redis->get('PHPREDIS_SESSION:' . $id); }, // read
    function($id, $data) use ($redis) { return $redis->set('PHPREDIS_SESSION:' . $id, $data); }, // write
    function($id) use ($redis) { return $redis->del('PHPREDIS_SESSION:' . $id); }, // destroy
    function($max_lifetime) use ($redis) { return true; } // gc
);
session_start();

require 'get-parameters.php';

// 데이터베이스 연결 파라미터 로드
$dbParams = getDbParameters();
$dbHost = $dbParams['endpoint'];
$dbUser = $dbParams['username']; // 데이터베이스 연결 사용자명
$dbPassword = $dbParams['password'];
$dbName = 'login'; // RDS의 데이터베이스 이름을 명확히 'login'으로 설정

// 데이터베이스 연결 함수 (PDO 사용)
function dbConnect($host, $username, $password, $dbname) {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}

// 세션 데이터를 Redis에서 가져오기
$redis = redisConnect();
$sessionKey = 'PHPREDIS_SESSION:' . session_id();
$sessionData = $redis->get($sessionKey);

if ($sessionData) {
    $sessionData = json_decode($sessionData, true);
    $userId = $sessionData['userid'];
    $username = $sessionData['username'];
} else {
    $userId = null;
    $username = null;
}

// EC2 인스턴스 메타데이터 가져오기
$rzaz = shell_exec('curl -s http://169.254.169.254/latest/meta-data/placement/availability-zone-id');
$iid = shell_exec('curl -s http://169.254.169.254/latest/meta-data/instance-id');
$lip = shell_exec('curl -s http://169.254.169.254/latest/meta-data/local-ipv4');
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WaggleBaggle</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            text-align: center;
            background-color: #fff;
            margin: 0;
            padding-top: 20px;
        }
        .logo-container {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .logo-container img {
            max-width: 150px;
            margin: 0;
        }
        h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 20px;
        }
        .nav {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            align-items: center;
        }
        .button {
            border: 2px solid #00a4db;
            background-color: transparent;
            color: #00a4db;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }
        .button:hover {
            background-color: #00a4db;
            color: #fff;
        }
        .metadata {
            margin: 20px 0;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        .item_recommend {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .item {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 200px;
        }
        .item img {
            max-width: 150px;
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .item span {
            margin-top: 8px;
            font-size: 1.1em;
        }
        .footer {
            margin-top: 40px;
            font-size: 0.8em;
            color: #777;
        }
        .user-icon {
            width: 60px;
            height: 60px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="https://dt4su2c9co90o.cloudfront.net/IMG/wagglebaggle.png" alt="WaggleBaggle Logo">
    </div>

    <div class="nav">
        <?php if ($username): ?>
            <img src="https://dt4su2c9co90o.cloudfront.net/IMG/usericon.png" alt="User Icon" class="user-icon">
            <span><?php echo htmlspecialchars($username); ?>님 환영합니다</span>
            <button class="button" onclick="location.href='logout.php';">로그아웃</button>
        <?php else: ?>
            <button class="button" onclick="location.href='login.php';">로그인</button>
            <button class="button" onclick="location.href='createuser.php';">회원가입</button>
        <?php endif; ?>
    </div>

    <div class="metadata">
        <div>Availability Zone ID: <?php echo htmlspecialchars($rzaz); ?></div>
        <div>Instance ID: <?php echo htmlspecialchars($iid); ?></div>
        <div>Local IP: <?php echo htmlspecialchars($lip); ?></div>
    </div>

    <h1>추천 상품</h1>
    <div class="item_recommend">
        <div class="item">
            <a href="item01.php">
                <img src="https://dt4su2c9co90o.cloudfront.net/IMG/img01.jpg" alt="베스트에버 강아지장난감 노즈워크장난감">
            </a>
            <span>베스트에버 강아지장난감 노즈워크장난감</span>
            <span>16,000원</span>
        </div>
        <div class="item">
            <a href="item02.php">
                <img src="https://dt4su2c9co90o.cloudfront.net/IMG/img02.jpg" alt="반려동물 푸쉬 반자동 급식기">
            </a>
            <span>반려동물 푸쉬 반자동 급식기</span>
            <span>43,000원</span>
        </div>
        <div class="item">
            <a href="item03.php">
                <img src="https://dt4su2c9co90o.cloudfront.net/IMG/img03.jpg" alt="공간활용 고양이 윈도우 해먹">
            </a>
            <span>공간활용 고양이 윈도우 해먹</span>
            <span>21,000원</span>
        </div>
    </div>

    <div class="footer">
        <p>Welcome WaggleBaggle 우리 애기에게 먹일 음식은 절대 함부로 제공하지 않습니다</p>
    </div>
</body>
</html>
