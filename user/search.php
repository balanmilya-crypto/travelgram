<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$users = [];
if (!empty($search)) {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $query = "SELECT id, name, avatar FROM users WHERE name LIKE '%$search_esc%' ORDER BY name LIMIT 20";
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - TravelGram</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', 'Poppins', sans-serif;
            background: #eff1ee;
            color: #1b1c27;
            padding: 20px;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ==== عناصر الخلفية الجمالية ==== */
        .bg-bubble {
            position: fixed;
            border-radius: 50%;
            background: rgba(101, 64, 58, 0.05);
            z-index: 0;
            pointer-events: none;
            animation: float 12s infinite alternate ease-in-out;
        }
        .bg-bubble:nth-child(1) { width: 300px; height: 300px; top: -50px; left: -50px; background: rgba(158, 193, 215, 0.08); animation-duration: 15s; }
        .bg-bubble:nth-child(2) { width: 500px; height: 500px; bottom: -100px; right: -100px; background: rgba(101, 64, 58, 0.06); animation-duration: 18s; }
        .bg-bubble:nth-child(3) { width: 200px; height: 200px; top: 40%; left: 10%; background: rgba(115, 121, 59, 0.05); animation-duration: 10s; }
        .bg-bubble:nth-child(4) { width: 400px; height: 400px; bottom: 20%; left: -150px; background: rgba(158, 193, 215, 0.07); animation-duration: 20s; }
        .bg-bubble:nth-child(5) { width: 250px; height: 250px; top: 15%; right: 5%; background: rgba(101, 64, 58, 0.04); animation-duration: 14s; }

        .bg-wave {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0; left: 0;
            z-index: 0;
            pointer-events: none;
            background: url('data:image/svg+xml;utf8,<svg width="100%" height="100%" viewBox="0 0 1200 800" xmlns="http://www.w3.org/2000/svg"><path d="M0,400 Q300,200 600,400 T1200,300 L1200,800 L0,800 Z" fill="rgba(158,193,215,0.05)"/><path d="M0,600 Q400,450 800,600 T1200,500 L1200,800 L0,800 Z" fill="rgba(101,64,58,0.03)"/></svg>');
            background-size: cover;
            opacity: 0.5;
        }

        @keyframes float {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-30px) scale(1.05); }
        }

        /* ==== الحاوية الرئيسية زجاجية ==== */
        .container {
            max-width: 650px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            border-radius: 40px;
            box-shadow: 0 30px 50px rgba(27, 28, 39, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 30px 35px;
            position: relative;
            z-index: 10;
        }

        /* ==== الروابط والأزرار ==== */
        .back-link {
            display: inline-block;
            margin-bottom: 25px;
            color: #65403a;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 18px;
            border-radius: 40px;
            background: rgba(101, 64, 58, 0.08);
        }
        .back-link i { margin-right: 8px; }
        .back-link:hover {
            background: #65403a;
            color: white;
            transform: translateX(-5px);
        }

        h2 {
            margin-bottom: 25px;
            color: #1b1c27;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .search-box {
            position: relative;
            margin-bottom: 25px;
        }
        .search-box input {
            width: 100%;
            padding: 14px 50px 14px 20px;
            border: 1.5px solid rgba(158, 193, 215, 0.4);
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
            background: white;
            font-family: inherit;
        }
        .search-box input:focus {
            border-color: #65403a;
            box-shadow: 0 0 0 4px rgba(101, 64, 58, 0.15);
        }
        .search-box button {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #65403a;
            font-size: 20px;
            transition: 0.3s;
        }
        .search-box button:hover { color: #4f322c; transform: translateY(-50%) scale(1.1); }

        .suggestions {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(158, 193, 215, 0.3);
            border-radius: 30px;
            max-height: 350px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: rgba(101, 64, 58, 0.05); }

        .suggestion-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
        }

        .suggestion-name { font-weight: 600; color: #1b1c27; }

        .user-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(158, 193, 215, 0.2);
            cursor: pointer;
            transition: 0.2s;
            border-radius: 30px;
        }
        .user-item:hover {
            background: rgba(101, 64, 58, 0.05);
            transform: translateX(5px);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #9ec1d7;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .avatar-initial {
            background: linear-gradient(145deg, #65403a, #9ec1d7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .user-name { font-weight: 700; color: #1b1c27; font-size: 1.1rem; }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #5a6a7a;
            font-size: 1.1rem;
        }

        @media (max-width: 600px) {
            .container { padding: 20px 18px; border-radius: 30px; }
            h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
<!-- عناصر الخلفية الزخرفية -->
<div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div><div class="bg-bubble"></div>
<div class="bg-wave"></div>

<div class="container">
    <a href="feed.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Feed</a>
    <h2><i class="fas fa-search mr-2" style="color:#65403a;"></i>Search Users</h2>
    
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Type a username..." autocomplete="off" value="<?php echo htmlspecialchars($search); ?>">
        <button id="searchBtn"><i class="fas fa-search"></i></button>
        <div id="suggestions" class="suggestions"></div>
    </div>

    <div id="searchResults">
        <?php if (!empty($search)): ?>
            <?php if (empty($users)): ?>
                <div class="no-results"><i class="fas fa-user-slash" style="font-size: 40px; opacity: 0.5; margin-bottom: 15px;"></i><br>No results found for "<?php echo htmlspecialchars($search); ?>"</div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-item" onclick="location.href='user_view.php?user_id=<?php echo $user['id']; ?>'">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo $user['avatar']; ?>" class="user-avatar" onerror="this.src='img/default-avatar.png'">
                        <?php else: ?>
                            <div class="user-avatar avatar-initial"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #5a6a7a;">
                <i class="fas fa-search" style="font-size: 50px; opacity: 0.5; margin-bottom: 20px;"></i>
                <p>Start typing to search for users</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        let searchTimeout;
        const searchInput = $('#searchInput');
        const suggestionsDiv = $('#suggestions');

        // Typeahead suggestions
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().trim();
            
            if (query.length === 0) {
                suggestionsDiv.hide().empty();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: 'search_ajax.php',
                    type: 'GET',
                    data: { q: query },
                    dataType: 'json',
                    success: function(data) {
                        suggestionsDiv.empty();
                        if (data.length === 0) {
                            suggestionsDiv.hide();
                            return;
                        }
                        $.each(data, function(index, user) {
                            let avatarHtml = '';
                            if (user.avatar && user.avatar.trim() !== '') {
                                avatarHtml = '<img src="' + user.avatar + '" class="suggestion-avatar" onerror="this.src=\'img/default-avatar.png\'">';
                            } else {
                                let firstLetter = user.name.charAt(0).toUpperCase();
                                avatarHtml = '<div class="suggestion-avatar avatar-initial" style="display: flex; align-items: center; justify-content: center;">' + firstLetter + '</div>';
                            }
                            const item = $('<div class="suggestion-item"></div>')
                                .html(avatarHtml + '<div class="suggestion-name">' + escapeHtml(user.name) + '</div>')
                                .on('click', function() {
                                    location.href = 'user_view.php?user_id=' + user.id;
                                });
                            suggestionsDiv.append(item);
                        });
                        suggestionsDiv.show();
                    },
                    error: function() {
                        suggestionsDiv.hide();
                    }
                });
            }, 300);
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-box').length) {
                suggestionsDiv.hide();
            }
        });

        // Perform full search
        function performSearch() {
            const query = searchInput.val().trim();
            if (query) {
                window.location.href = 'search.php?q=' + encodeURIComponent(query);
            }
        }

        $('#searchBtn').on('click', performSearch);
        searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                performSearch();
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    });
</script>
</body>
</html>