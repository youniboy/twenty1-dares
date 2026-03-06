<?php
session_start();

function randomLine($filePath) {
    if (!file_exists($filePath)) {
        return null;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines || count($lines) === 0) {
        return null;
    }

    $index = array_rand($lines);
    return trim($lines[$index]);
}

function getPrompt($category, $mode) {
    $map = [
        'normal' => [
            'truth' => 'questions/truth_normal.txt',
            'dare' => 'questions/dare_normal.txt',
            'doubledare' => 'questions/doubledare_normal.txt',
            'situation' => 'questions/situation_normal.txt',
            'burninghouse' => 'questions/burninghouse_normal.txt',
        ],
        'spicy' => [
            'truth' => 'questions/truth_spicy.txt',
            'dare' => 'questions/dare_spicy.txt',
            'doubledare' => 'questions/doubledare_spicy.txt',
            'situation' => 'questions/situation_spicy.txt',
            'burninghouse' => 'questions/burninghouse_spicy.txt',
        ]
    ];

    if (!isset($map[$mode][$category])) {
        return null;
    }

    return randomLine($map[$mode][$category]);
}

function resetFullGame() {
    $_SESSION['players'] = 0;
    $_SESSION['mode'] = '';
    $_SESSION['current_player'] = 1;
    $_SESSION['starting_player'] = 1;
    $_SESSION['current_number'] = 0;
    $_SESSION['game_started'] = false;
    $_SESSION['awaiting_activity'] = false;
    $_SESSION['loser_player'] = null;
    $_SESSION['last_spoken'] = [];
    $_SESSION['selected_category'] = null;
    $_SESSION['selected_prompt'] = null;
    $_SESSION['round_number'] = 1;
    $_SESSION['round_history'] = [];
}

function resetCountingRoundFromLoser() {
    $loser = $_SESSION['loser_player'] ?? 1;

    $_SESSION['current_number'] = 0;
    $_SESSION['current_player'] = $loser;
    $_SESSION['starting_player'] = $loser;
    $_SESSION['awaiting_activity'] = false;
    $_SESSION['last_spoken'] = [];
    $_SESSION['selected_category'] = null;
    $_SESSION['selected_prompt'] = null;
    $_SESSION['loser_player'] = null;
    $_SESSION['round_number'] = ($_SESSION['round_number'] ?? 1) + 1;
}

function formatCategoryLabel($category) {
    $labels = [
        'truth' => 'Truth',
        'dare' => 'Dare',
        'doubledare' => 'Double Dare',
        'situation' => 'Situation',
        'burninghouse' => 'Burning House',
    ];

    return $labels[$category] ?? ucfirst($category);
}

function renderActivityOutput($category, $prompt, $loserPlayer) {

    if ($category === 'burninghouse') {
        $mode = $_SESSION['mode'] ?? 'normal';

        if (!isset($_POST['burn_names'])) {
            return "
                <div class='question-card'>
                    <div class='question-tag'>🔥 Burning House</div>
                    <p class='question-main'>Other players suggest the 3 names:</p>

                    <form method='post'>
                        <input type='hidden' name='select_category' value='burninghouse'>

                        <input class='setup-input' name='name1' placeholder='Name 1' required>
                        <br><br>

                        <input class='setup-input' name='name2' placeholder='Name 2' required>
                        <br><br>

                        <input class='setup-input' name='name3' placeholder='Name 3' required>
                        <br><br>

                        <button class='button' name='burn_names'>Continue</button>
                    </form>
                </div>
            ";
        }

        $name1 = htmlspecialchars($_POST['name1'] ?? '');
        $name2 = htmlspecialchars($_POST['name2'] ?? '');
        $name3 = htmlspecialchars($_POST['name3'] ?? '');

        if ($mode === 'spicy') {
            $action1 = "💍 Marry";
            $action2 = "❤️ Hookup";
            $action3 = "🔥 One Night Stand";
        } else {
            $action1 = "💍 Marry";
            $action2 = "❤️ Date";
            $action3 = "🚫 Avoid";
        }

        return "
            <div class='question-card'>
                <div class='question-tag'>🔥 Burning House</div>
                <p class='question-main'>Player " . (int)$loserPlayer . " must assign the names below.</p>

                <div style='margin-top:22px;'>
                    <div class='option-box' style='margin-bottom:18px;'>
                        <div class='option-title'>The 3 Names</div>
                        <p>$name1</p>
                        <p>$name2</p>
                        <p>$name3</p>
                    </div>

                    <div class='option-grid'>
                        <div class='option-box'>
                            <div class='option-title'>$action1</div>
                            <p>Choose one</p>
                        </div>

                        <div class='option-box'>
                            <div class='option-title'>$action2</div>
                            <p>Choose one</p>
                        </div>

                        <div class='option-box'>
                            <div class='option-title'>$action3</div>
                            <p>Choose one</p>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }

    if ($category === 'doubledare') {
        if (!$prompt) {
            return "<p class='highlight-question'>No prompt found for Double Dare.</p>";
        }

        $parts = array_map('trim', explode('|', $prompt));

        if (count($parts) < 2) {
            return "<p class='highlight-question'>Double Dare format is invalid. Use: Dare 1 | Dare 2</p>";
        }

        $dare1 = htmlspecialchars($parts[0]);
        $dare2 = htmlspecialchars($parts[1]);

        return "
            <div class='question-card'>
                <div class='question-tag'>⚡ Double Dare</div>
                <p class='question-main'>Player " . (int)$loserPlayer . " must choose one:</p>

                <div class='option-grid two-cols'>
                    <div class='option-box'>
                        <div class='option-title'>This</div>
                        <p>$dare1</p>
                    </div>

                    <div class='option-box'>
                        <div class='option-title'>That</div>
                        <p>$dare2</p>
                    </div>
                </div>
            </div>
        ";
    }

    if (!$prompt) {
        return "<p class='highlight-question'>No prompt found for this category.</p>";
    }

    return "
        <div class='question-card'>
            <div class='question-tag'>" . htmlspecialchars(formatCategoryLabel($category)) . "</div>
            <p class='question-main'>" . htmlspecialchars($prompt) . "</p>
        </div>
    ";
}

if (!isset($_SESSION['initialized'])) {
    $_SESSION['initialized'] = true;
    resetFullGame();
}

$message = "Set up the game to begin.";
$activityOutput = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['reset_game'])) {
        resetFullGame();
        $message = "Game reset successfully.";
    }

    if (isset($_POST['start_game'])) {
        $players = isset($_POST['players']) ? (int)$_POST['players'] : 0;
        $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
    
        if ($players <= 1) {
            $message = '
                <div class="question-card">
                    <div class="question-tag">🚫 Not Enough Players</div>
    
                    <p class="question-main">
                        This game needs at least <b>2 players</b>.
                    </p>
    
                    <p style="color:#fff;margin-top:10px">
                        Truths and dares are much more fun when you have someone to blame.
                        Grab a friend… or a few troublemakers.
                    </p>
                </div>
            ';
    
            $_SESSION['game_started'] = false;
        }
        else if (in_array($mode, ['normal', 'spicy'], true)) {
            $_SESSION['players'] = $players;
            $_SESSION['mode'] = $mode;
            $_SESSION['current_player'] = 1;
            $_SESSION['starting_player'] = 1;
            $_SESSION['current_number'] = 0;
            $_SESSION['game_started'] = true;
            $_SESSION['awaiting_activity'] = false;
            $_SESSION['loser_player'] = null;
            $_SESSION['last_spoken'] = [];
            $_SESSION['selected_category'] = null;
            $_SESSION['selected_prompt'] = null;
            $_SESSION['round_number'] = 1;
            $_SESSION['round_history'] = [];
    
            $message = "Game started! Round 1 begins with Player 1 in " . ucfirst($mode) . " mode.";
        } else {
            $message = "Please enter valid players and select a mode.";
        }
    }

    if (isset($_POST['play_step'])) {
        if (!empty($_SESSION['game_started']) && empty($_SESSION['awaiting_activity'])) {
            $step = (int)$_POST['play_step'];

            if ($step >= 1 && $step <= 3) {
                $spoken = [];

                for ($i = 0; $i < $step; $i++) {
                    if ($_SESSION['current_number'] < 21) {
                        $_SESSION['current_number']++;
                        $spoken[] = $_SESSION['current_number'];

                        if ($_SESSION['current_number'] == 21) {
                            $_SESSION['awaiting_activity'] = true;
                            $_SESSION['loser_player'] = $_SESSION['current_player'];
                            break;
                        }
                    }
                }

                $_SESSION['last_spoken'] = $spoken;

                if ($_SESSION['awaiting_activity']) {
                    $message = "Player " . $_SESSION['current_player'] . " said 21 and must do the activity.";
                } else {
                    $nextPlayer = ($_SESSION['current_player'] % $_SESSION['players']) + 1;
                    $message = "Player " . $_SESSION['current_player'] . " said: " . implode(', ', $spoken) . ". Next turn: Player " . $nextPlayer . ".";
                    $_SESSION['current_player'] = $nextPlayer;
                }
            }
        }
    }

    if (isset($_POST['select_category'])) {
        if (!empty($_SESSION['awaiting_activity']) && !empty($_SESSION['loser_player'])) {
            $category = $_POST['select_category'];
            $mode = $_SESSION['mode'] ?? 'normal';

            $_SESSION['selected_category'] = $category;
            $_SESSION['selected_prompt'] = getPrompt($category, $mode);

            $activityOutput = renderActivityOutput(
                $_SESSION['selected_category'],
                $_SESSION['selected_prompt'],
                $_SESSION['loser_player']
            );
        }
    }

    if (isset($_POST['next_round'])) {
        if (!empty($_SESSION['awaiting_activity']) && !empty($_SESSION['loser_player'])) {
            $historyItem = [
                'round' => $_SESSION['round_number'],
                'loser' => $_SESSION['loser_player'],
                'category' => $_SESSION['selected_category'] ?: '-',
                'mode' => $_SESSION['mode'] ?: '-',
            ];

            $_SESSION['round_history'][] = $historyItem;

            $loser = $_SESSION['loser_player'];
            resetCountingRoundFromLoser();

            $message = "Round " . $_SESSION['round_number'] . " begins. Player " . $loser . " starts this round.";
        }
    }
}

$currentNumber = $_SESSION['current_number'] ?? 0;
$players = $_SESSION['players'] ?? 0;
$mode = $_SESSION['mode'] ?? '';
$currentPlayer = $_SESSION['current_player'] ?? 1;
$startingPlayer = $_SESSION['starting_player'] ?? 1;
$gameStarted = $_SESSION['game_started'] ?? false;
$awaitingActivity = $_SESSION['awaiting_activity'] ?? false;
$loserPlayer = $_SESSION['loser_player'] ?? null;
$lastSpoken = $_SESSION['last_spoken'] ?? [];
$roundNumber = $_SESSION['round_number'] ?? 1;
$roundHistory = $_SESSION['round_history'] ?? [];

if (empty($activityOutput) && !empty($_SESSION['selected_category']) && !empty($_SESSION['selected_prompt'])) {
    $activityOutput = renderActivityOutput(
        $_SESSION['selected_category'],
        $_SESSION['selected_prompt'],
        $loserPlayer
    );
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>21 Dares | Local Online Game</title>
    <meta charset="utf-8">

    <link rel="shortcut icon" href="img/favicon.png" type="image/png" />
    <link href="css/style.css" rel="stylesheet" type="text/css" media="screen" />
    <link href='https://fonts.googleapis.com/css?family=Montserrat:400,300,600,700,800' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">

    <style>
        .content-wrap{
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:flex-start;
            min-height:420px;
        }

        .setup-box{
            width:100%;
            max-width:1100px;
            min-height:420px;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            margin:0 auto 30px auto;
            background:rgba(255,255,255,0.08);
            padding:30px;
            border-radius:18px;
        }

        .setup-title{
            color:#fff;
            font-family:Montserrat;
            font-weight:700;
            margin-bottom:15px;
        }

        .setup-text{
            color:#fff;
            font-family:Montserrat;
            margin-bottom:12px;
            line-height:1.6;
        }

        .setup-input{
            width:140px;
            padding:12px;
            border-radius:10px;
            border:none;
            outline:none;
            font-family:Montserrat;
            font-size:16px;
            text-align:center;
            margin-bottom:18px;
        }

        .mode-row{
            display:flex;
            gap:14px;
            justify-content:center;
            flex-wrap:wrap;
            margin:15px 0 20px 0;
        }

        .mode-card{
            color:#fff;
            font-family:Montserrat;
            background:rgba(255,255,255,0.08);
            border-radius:14px;
            padding:14px 18px;
            display:flex;
            align-items:center;
            gap:10px;
        }

        .comingsoon{
            width:100%;
            max-width:1100px;
            min-height:420px;
            display:flex;
            justify-content:center;
            align-items:center;    
            margin:0 auto 30px auto;
        }

        .mode-card input{
            transform:scale(1.2);
        }

        .game-stats{
            color:#68b2b1;
            font-family:Montserrat;
            font-weight:600;
            margin-top:10px;
        }

        .counter-options{
            display:flex;
            gap:12px;
            justify-content:center;
            flex-wrap:wrap;
            margin-top:20px;
            margin-bottom:20px;
        }

        .activity-buttons{
            margin-top:25px;
        }

        .activity-buttons form,
        .counter-options form{
            display:inline-block;
            margin:6px;
        }

        .winner-box{
            background:rgba(255,255,255,0.08);
            border-radius:16px;
            padding:20px;
            margin-top:15px;
        }

        .next-round-box{
            margin-top:20px;
        }

        .history-box{
            max-width:700px;
            margin:20px auto 0 auto;
            background:rgba(255,255,255,0.06);
            padding:18px;
            border-radius:14px;
            text-align:left;
        }

        .history-box p{
            color:#fff;
            font-family:Montserrat;
            margin:6px 0;
        }

        #test{
            width:100%;
            max-width:1100px;
            margin:0 auto;
        }

        .question-card{
            width:100%;
            max-width:900px;
            margin:0 auto;
            background:linear-gradient(135deg, rgba(255,255,255,0.14), rgba(255,255,255,0.06));
            border-radius:20px;           
            padding:30px;
            box-shadow:0 10px 30px rgba(0,0,0,0.18);
        }

        .question-tag{
            display:inline-block;
            background:#68b2b1;
            color:#fff;
            font-family:Montserrat;
            font-weight:700;
            font-size:13px;
            letter-spacing:0.5px;
            padding:8px 14px;
            border-radius:999px;
            margin-bottom:14px;
            text-transform:uppercase;
        }

        .question-main{
            color:#fff;
            font-family:Montserrat;
            font-weight:700;
            font-size:26px;
            line-height:1.7;
            margin:0;
        }

        .highlight-question{
            color:#fff;
            font-family:Montserrat;
            font-weight:700;
            font-size:22px;
        }

        #test{
            width:100%;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
        }

        .option-grid{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:16px;
            margin-top:20px;
        }

        .option-grid.two-cols{
            grid-template-columns:repeat(2, 1fr);
        }

        .option-box{
            background:rgba(255,255,255,0.08);
            border-radius:16px;
            padding:18px;
            color:#fff;
            font-family:Montserrat;
        }

        .option-title{
            font-weight:700;
            font-size:18px;
            margin-bottom:12px;
            color:#68b2b1;
        }

        @media (max-width: 768px){
            .option-grid,
            .option-grid.two-cols{
                grid-template-columns:1fr;
            }

            .question-main{
                font-size:20px;
            }
        }
    </style>
</head>

<body>
<div class="wrapper">
    <div class='oss-widget-interface'></div>

    <div class="header">
        <div class="outer">
            <div class="inner">
                <h1 id="logo" style="font-weight:bold;">Twenty1 Dares</h1>
            </div>
        </div>

        <div class="content-wrap">

            <?php if (!$gameStarted): ?>
                <div class="setup-box">
                    <h2 class="setup-title">Start the 21 Game</h2>
                    <p class="setup-text">
                        Enter the number of players, then choose the mode. Each player can say <strong>1, 2, or 3</strong> numbers.
                        Whoever lands on <strong>21</strong> must do the activity.
                    </p>

                    <form method="post">
                        <input
                            type="number"
                            name="players"
                            class="setup-input"
                            min="1"
                            placeholder="Players"
                            required
                        />

                        <div class="mode-row">
                            <label class="mode-card">
                                <input type="radio" name="mode" value="normal" required>
                                <span>Normal</span>
                            </label>

                            <label class="mode-card">
                                <input type="radio" name="mode" value="spicy" required>
                                <span>Spicy</span>
                            </label>
                        </div>

                        <button type="submit" name="start_game" class="button">Start Game</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="comingsoon">
            <div id="test">
    <?php if ($awaitingActivity && $loserPlayer): ?>
        <div class="winner-box">
            <p style="color:#ff6666;font-weight:bold;font-size:26px;font-family:Montserrat;">
                Player <?php echo (int)$loserPlayer; ?> said 21!
            </p>

            <?php if (!empty($lastSpoken)): ?>
                <p style="color:#fff;font-weight:bold;font-family:Montserrat;">
                    Spoken numbers: <?php echo htmlspecialchars(implode(', ', $lastSpoken)); ?>
                </p>
            <?php endif; ?>

            <p style="color:#fff;font-family:Montserrat;">
                Choose the activity for Player <?php echo (int)$loserPlayer; ?>.
            </p>
        </div>
    <?php else: ?>
        <?php if (strpos($message, '<div') !== false): ?>
            <?php echo $message; ?>
        <?php else: ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php echo $activityOutput; ?>

    <?php if ($awaitingActivity && $loserPlayer && !empty($_SESSION['selected_category'])): ?>
        <div class="next-round-box">
            <form method="post">
                <button type="submit" name="next_round" class="button button3">Start Next Round</button>
            </form>
        </div>
    <?php endif; ?>
</div>
            </div>

            <?php if ($gameStarted && !$awaitingActivity): ?>
                <div class="content">
                    <div class="counter-options">
                        <form method="post">
                            <button type="submit" name="play_step" value="1" class="button">+1</button>
                        </form>

                        <form method="post">
                            <button type="submit" name="play_step" value="2" class="button">+2</button>
                        </form>

                        <form method="post">
                            <button type="submit" name="play_step" value="3" class="button">+3</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($awaitingActivity && $loserPlayer): ?>
                <div class="content activity-buttons">
                    <form method="post">
                        <button type="submit" name="select_category" value="truth" class="button">Truth</button>
                    </form>

                    <form method="post">
                        <button type="submit" name="select_category" value="dare" class="button">Dare</button>
                    </form>

                    <form method="post">
                        <button type="submit" name="select_category" value="doubledare" class="button">Double Dare</button>
                    </form>

                    <form method="post">
                        <button type="submit" name="select_category" value="situation" class="button">Situation</button>
                    </form>

                    <form method="post">
                        <button type="submit" name="select_category" value="burninghouse" class="button">Burning House</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!empty($roundHistory)): ?>
                <div class="history-box">
                    <p style="font-weight:bold;">Round History</p>
                    <?php foreach (array_reverse($roundHistory) as $item): ?>
                        <p>
                            Round <?php echo (int)$item['round']; ?>:
                            Player <?php echo (int)$item['loser']; ?> lost
                            — Mode: <?php echo htmlspecialchars(ucfirst($item['mode'])); ?>
                            <?php if (!empty($item['category']) && $item['category'] !== '-'): ?>
                                — Activity: <?php echo htmlspecialchars(formatCategoryLabel($item['category'])); ?>
                            <?php endif; ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="social">
        <br/>
        <p class="game-stats">Mode = <?php echo $mode ? htmlspecialchars(ucfirst($mode)) : '-'; ?></p>
        <p class="game-stats">Round = <?php echo (int)$roundNumber; ?></p>
        <p class="game-stats">Current Number = <?php echo (int)$currentNumber; ?></p>
        <p class="game-stats">Players = <?php echo (int)$players; ?></p>
        <p class="game-stats">Starting Player This Round = Player <?php echo (int)$startingPlayer; ?></p>

        <?php if ($awaitingActivity && $loserPlayer): ?>
            <p class="game-stats">Landed on 21 = Player <?php echo (int)$loserPlayer; ?></p>
        <?php elseif ($gameStarted): ?>
            <p class="game-stats">Current Turn = Player <?php echo (int)$currentPlayer; ?></p>
        <?php else: ?>
            <p class="game-stats">Current Turn = -</p>
        <?php endif; ?>

        <p class="game-stats">
            Spoken Numbers =
            <?php echo !empty($lastSpoken) ? htmlspecialchars(implode(', ', $lastSpoken)) : '-'; ?>
        </p>

        <br/>

        <form method="post">
            <button type="submit" name="reset_game" class="button button1 button2">Restart Full Game</button>
        </form>

        <br/><br/>

        <a href="https://www.facebook.com/" class="fa-stack fa-lg">
            <i class="fa fa-circle fa-stack-2x"></i>
            <i class="fa fa-facebook fa-stack-1x fa-inverse"></i>
        </a>
        <a href="https://twitter.com/" class="fa-stack fa-lg">
            <i class="fa fa-circle fa-stack-2x"></i>
            <i class="fa fa-twitter fa-stack-1x fa-inverse"></i>
        </a>
        <a href="https://www.linkedin.com/in/" class="fa-stack fa-lg">
            <i class="fa fa-circle fa-stack-2x"></i>
            <i class="fa fa-linkedin fa-stack-1x fa-inverse"></i>
        </a>
    </div>
</div>

<script src="js/index.js"></script>
</body>
</html>