<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_deck_id'])) {
    $deck_id = (int)$_POST['delete_deck_id'];
    
    $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ?");
    $stmt->execute([$deck_id]);
    
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_decks = [];

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM decks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $user_decks = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duel Masters Deck Builder</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <h1>DM<span>Builder</span></h1>
        </div>
        <nav class="user-nav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="builder.php" class="btn-header-create">Create Deck</a>
                <div class="dropdown">
                    <button class="dropdown-trigger" id="userMenuBtn">
                        <?php echo htmlspecialchars($_SESSION['username']); ?> &#9662;
                    </button>
                    <div class="dropdown-menu" id="userMenu">
                        <a href="account.php">Profile Settings</a>
                        <a href="logout.php">Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="controls-bar">
        <div class="search-wrapper">
            <input type="text" id="deckSearch" placeholder="Search Deck Name...">
        </div>
        <div class="filter-wrapper">
            <select id="dateSort">
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
            </select>
        </div>
    </section>

    <main class="dashboard-content">
        <?php if (empty($user_decks)): ?>
            <div class="empty-state">
                <div class="empty-message">
                    <h2>No decks found</h2>
                    <p>Start your journey by creating your first Duel Masters deck.</p>
                    <a href="builder.php">
                        <button class="btn-primary">Create Your First Deck</button>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="deck-grid">
                <?php foreach ($user_decks as $deck): ?>
                    <article class="deck-card" data-created="<?php echo strtotime($deck['created_at']); ?>">
                        <a href="builder.php?deck_id=<?php echo $deck['id']; ?>" class="deck-link-wrapper">
                            <div class="card-thumbnail">
                                <img src="<?php echo htmlspecialchars($deck['cover_image'] ?: 'https://via.placeholder.com/300x200?text=DM+Deck'); ?>" alt="Deck Thumbnail">
                            </div>
                            <div class="card-info">
                                <h3><?php echo htmlspecialchars($deck['deck_name'] ?? $deck['name']); ?></h3>
                            </div>
                        </a>
                        <form method="POST" class="delete-form" onsubmit="return confirm('Delete this deck?');">
                            <input type="hidden" name="delete_deck_id" value="<?php echo $deck['id']; ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const menuBtn = document.getElementById('userMenuBtn');
        const menu = document.getElementById('userMenu');
        
        if (menuBtn && menu) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('show');
            });

            document.addEventListener('click', () => {
                menu.classList.remove('show');
            });
        }

        const searchInput = document.getElementById('deckSearch');
        const dateSort = document.getElementById('dateSort');
        const deckGrid = document.querySelector('.deck-grid');

        function filterDecks() {
            const query = searchInput.value.toLowerCase();
            const sortOrder = dateSort.value;
            const deckCards = Array.from(document.querySelectorAll('.deck-card'));

            deckCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const matchesSearch = name.includes(query);
                card.style.display = matchesSearch ? 'block' : 'none';
            });

            const sortedCards = deckCards.sort((a, b) => {
                const dateA = parseInt(a.dataset.created);
                const dateB = parseInt(b.dataset.created);
                return sortOrder === 'desc' ? dateB - dateA : dateA - dateB;
            });

            sortedCards.forEach(card => deckGrid.appendChild(card));
        }

        searchInput.addEventListener('keyup', filterDecks);
        dateSort.addEventListener('change', filterDecks);
    </script>
</body>
</html>