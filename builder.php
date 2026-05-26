<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'db_connect.php';

$deck_name = "New Deck";
$card_list_json = "[]";
$deck_id = $_GET['deck_id'] ?? null;

if ($deck_id) {
    $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ? AND user_id = ?");
    $stmt->execute([$deck_id, $_SESSION['user_id']]);
    $deck = $stmt->fetch();
    
    if ($deck) {
        $deck_name = $deck['deck_name'];
        $card_list_json = $deck['card_list'];
    } else {
        $deck_id = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deck Builder - DMBuilder</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="builder-page">

    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

    <header class="main-header">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <h1>DM<span>Builder</span></h1>
            </a>
        </div>
        <nav class="user-nav">
            <div class="dropdown">
                <button class="dropdown-trigger" id="userMenuBtn">
                    <?php echo htmlspecialchars($_SESSION['username']); ?> &#9662;
                </button>
                <div class="dropdown-menu" id="userMenu">
                    <a href="account.php">Profile Settings</a>
                    <a href="logout.php">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="builder-layout compact-layout">
        <section class="library-column" id="library-column">
            <div class="column-header sticky-bar compact-header">
                <div class="search-row">
                    <input type="text" id="cardSearch" placeholder="Search cards...">
                    <button id="openFiltersBtn" class="btn-secondary">Filters</button>
                </div>
            </div>
            <div class="scrollable-area card-library-scroll">
                <div class="card-grid" id="card-library-grid">
                </div>
            </div>
        </section>

        <section class="deck-column">
            <div class="column-header sticky-bar deck-controls">
                <input type="text" id="deckNameInput" value="<?php echo htmlspecialchars($deck_name); ?>" placeholder="Enter deck name...">
                <div class="deck-stats">
                    <button class="btn-secondary" id="openPlaytestBtn" style="padding: 0.8rem 1.5rem;">Playtest</button>
                    <button class="btn-primary" id="saveDeckBtn" style="height: 42px; padding: 0 15px; box-sizing: border-box;">Save Deck</button>
                </div>
            </div>
            <div class="deck-header-info" style="padding: 10px; background: var(--surface-color); border-bottom: 1px solid var(--border-color); font-weight: bold; text-align: center;">Main Deck (<span id="count-main">0 / 60</span>)</div>
            <div class="action-bar">
                <button id="clearDeckBtn" class="btn-danger">Clear Deck</button>
                <div class="sort-group">
                    <label for="sortBy">Sort By:</label>
                    <select id="sortBy">
                        <option value="order">Order Added</option>
                        <option value="cost">Cost</option>
                        <option value="civilization">Civilization</option>
                        <option value="copies">Copies</option>
                    </select>
                </div>
            </div>
            <div class="scrollable-area">
                <div id="main-container" class="zone-container active-zone">
                    <div id="active-main-deck" class="active-deck-list"></div>
                </div>
            </div>
        </section>
    </div>

    <div id="playtestModal" class="playtest-modal hidden">
        <div class="playtest-content">
            <header class="playtest-header">
                <h2>Simulator</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button id="resetPlaytestBtn" class="btn-secondary" style="padding: 5px 15px;">Reset</button>
                    <button id="closePlaytestBtn" class="btn-danger">Exit Simulator</button>
                </div>
            </header>
            
            <div class="playtest-layout" style="display: flex; flex-direction: column; gap: 20px; height: 100%; padding: 20px;">
                
                <div class="playtest-top-row" style="display: flex; gap: 20px; flex: 1; overflow: hidden;">
                    <div class="field-column" style="flex: 1; display: flex; flex-direction: column; gap: 10px; overflow: hidden;">
                        <div id="battle-zone" class="playtest-zone">
                            <span class="zone-label">BATTLE ZONE</span>
                            <div class="card-row"></div>
                        </div>
                        <div id="shields-zone" class="playtest-zone">
                            <span class="zone-label">SHIELD ZONE</span>
                            <div class="card-row"></div>
                        </div>
                        <div id="mana-zone" class="playtest-zone">
                            <span class="zone-label">MANA ZONE</span>
                            <div class="card-row"></div>
                        </div>
                    </div>

                    <aside class="pile-column" style="width: 220px; display: flex; flex-direction: column; gap: 15px;">
                        <div id="deck-pile" class="pile-group" style="height: auto; min-height: 220px; position: relative; border: 2px solid #333; border-radius: 8px; padding: 10px; display: flex; flex-direction: column; align-items: center;">
                            <div class="zone-label" style="align-self: flex-start;">Deck Pile</div>
                            <div id="deck-count-container" style="margin-bottom: 25px; text-align: center;">
                                <div id="deck-count" style="font-size: 3rem; color: #00bcd4; margin-top: 0;">0</div>
                            </div>

                            <div class="deck-controls" style="margin-top: 30px; width: 100%; display: flex; flex-direction: column; gap: 10px; padding: 0 10px; box-sizing: border-box;">
                                <div style="display: flex; gap: 10px; justify-content: space-between;">
                                    <button id="drawCardBtn" class="btn-secondary" style="flex: 1;">Draw</button>
                                    <button id="searchDeckBtn" class="btn-secondary" style="flex: 1;">Search</button>
                                </div>
                                <button id="shuffleBtn" class="btn-secondary" style="width: 100%;">Shuffle</button>
                            </div>
                        </div>

                    
                        <div class="pile-group" style="height: auto; min-height: 110px; border: 2px solid #333; border-radius: 8px; padding: 10px; position: relative; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: space-between; width: 100%; z-index: 10; margin-bottom: 5px;">
                                <div class="zone-label" style="position: static;">Graveyard</div>
                                <button id="viewGraveBtn" class="btn-secondary" style="font-size: 0.7rem; padding: 2px 5px; cursor: pointer;">View</button>
                            </div>
                            <div id="graveyard-zone" class="drop-zone card-pile" style="width: 100%; flex: 1; position: relative;">
                                <div class="card-row"></div>
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="playtest-bottom-row" style="width: 100%; overflow: visible; min-height: 180px; background: rgba(0, 0, 0, 0.4); border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                    <div id="hand-zone" class="playtest-zone" style="height: 100%;">
                        <span class="zone-label">HAND</span>
                        <div class="card-row"></div>
                    </div>
                </div>
            </div>

            <div id="deckReturnModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:10000; background:#1a1a1a; padding:30px; border-radius:10px; border:2px solid #00bcd4; text-align:center; box-shadow: 0 8px 25px rgba(0,0,0,0.7); min-width: 350px;">
                <p style="color:white; margin-bottom:20px; font-size: 1.2rem; font-weight:bold;">Return card to Deck:</p>
                <div style="display:flex; gap:15px; justify-content:center; align-items:center;">
                    <button id="btnReturnTop" class="btn-primary" style="flex: 1; height: 42px; padding: 0; box-sizing: border-box; margin: 0; border-radius: 6px;">Top</button>
                    <button id="btnReturnBottom" class="btn-primary" style="flex: 1; height: 42px; padding: 0; box-sizing: border-box; margin: 0; border-radius: 6px;">Bottom</button>
                    <button id="btnReturnCancel" class="btn-secondary" style="flex: 1; height: 42px; padding: 0; box-sizing: border-box; margin: 0; border-radius: 6px;">Cancel</button>
                </div>
            </div>

            <div id="zoneViewerModal" class="zone-viewer-modal hidden">
                <div class="zone-viewer-header">
                    <h3 id="zoneViewerTitle" style="margin:0; color:var(--accent-color);">Viewing Zone</h3>
                    <button id="closeZoneViewerBtn" class="btn-secondary" style="margin:0; padding: 5px 15px;">Close</button>
                </div>
                <div id="zoneViewerGrid" class="zone-viewer-grid"></div>
            </div>
        </div>
    </div>

    <script>
        const loadedDeckIds = <?php echo $card_list_json; ?>;
        const loadedDeckId = <?php echo $deck_id ? (int)$deck_id : 'null'; ?>;
    </script>

    <div id="save-modal" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <header>
                <h2>Confirm Deck Details</h2>
            </header>
            <div class="modal-body">
                <div class="modal-preview">
                    <img id="modalCoverPreview" src="" alt="Cover Preview">
                </div>
                <div class="modal-form">
                    <div class="form-group">
                        <label for="modalDeckName">Deck Name</label>
                        <input type="text" id="modalDeckName" placeholder="Enter deck name...">
                    </div>
                    <div class="form-group">
                        <label for="modalCoverImage">Cover Image</label>
                        <select id="modalCoverImage"></select>
                    </div>
                </div>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn-secondary" id="cancelSaveBtn">Cancel</button>
                <button type="button" class="btn-primary" id="confirmSaveBtn">Confirm Save</button>
            </footer>
        </div>
    </div>

    <div id="filter-modal" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <header><h2>Advanced Filters</h2></header>
            <div class="modal-body">
                <div class="form-group">
                    <label>Civilization</label>
                    <select id="filterCiv">
                        <option value="">All Civilizations</option>
                        <option value="fire">Fire</option>
                        <option value="water">Water</option>
                        <option value="nature">Nature</option>
                        <option value="light">Light</option>
                        <option value="darkness">Darkness</option>
                        <option value="multicolor">Multicolor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select id="filterType">
                        <option value="">All Types</option>
                        <option value="creature">Creature</option>
                        <option value="spell">Spell</option>
                        <option value="cross gear">Cross Gear</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cost Range</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <select id="filterCostMin" style="flex: 1;">
                            <option value="">Min</option>
                            <?php for($i=0; $i<=15; $i++) echo "<option value='$i'>$i</option>"; ?>
                        </select>
                        <span style="color: var(--text-secondary);">to</span>
                        <select id="filterCostMax" style="flex: 1;">
                            <option value="">Max</option>
                            <?php for($i=0; $i<=15; $i++) echo "<option value='$i'>$i</option>"; ?>
                        </select>
                    </div>
                </div>
                <button id="clearFiltersBtn" class="btn-secondary">Clear All Filters</button>
            </div>
            <footer class="modal-footer">
                <button type="button" class="btn-primary" id="closeFiltersBtn">Apply Filters</button>
            </footer>
        </div>
    </div>

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
    </script>
    <div id="cardPreviewContainer" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 100000; pointer-events: none; background: #0d1117; padding: 10px; border-radius: 12px; border: 2px solid #30363d; box-shadow: 0 15px 35px rgba(0,0,0,0.8);">
        <img id="cardPreviewImage" src="" alt="Card Preview" style="width: 220px; height: auto; display: block; border-radius: 6px;">
    </div>
    <script src="app.js"></script>
</body>
</html>