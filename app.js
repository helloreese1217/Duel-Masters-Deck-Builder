let activeDeck = { main: [] };
let allCards = [];
let playtestState = { deck: [], hand: [], shields: [], mana: [], battle: [], graveyard: [] };
let currentViewerZone = null;

let pendingDeckCardId = null;
let pendingSourceZone = null;

document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.getElementById('saveDeckBtn');

    fetch('cards.json')
        .then(response => {
            if (!response.ok) throw new Error('Failed to load card library');
            return response.json();
        })
        .then(cardData => {
            allCards = cardData;
            renderCardLibrary(allCards);
            
            if (typeof loadedDeckIds !== 'undefined') {
                const mainIds = loadedDeckIds.main || (Array.isArray(loadedDeckIds) ? loadedDeckIds : []);
                mainIds.forEach(id => {
                    const card = allCards.find(c => c.id === id);
                    if (card) activeDeck.main.push(card);
                });
                renderActiveDeck();
            }
        })
        .catch(error => {});

    if (saveBtn) {
        saveBtn.addEventListener('click', saveDeck);
    }

    document.getElementById('card-library-grid')?.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.btn-add');
        if (addBtn) {
            const cardId = addBtn.dataset.cardId;
            const card = allCards.find(c => String(c.id) === String(cardId));
            if (card) addCardToDeck(card);
        }
    });

    document.getElementById('openFiltersBtn')?.addEventListener('click', () => {
        const filterModal = document.getElementById('filter-modal');
        if (filterModal) filterModal.classList.remove('hidden');
    });
    document.getElementById('closeFiltersBtn')?.addEventListener('click', () => {
        const filterModal = document.getElementById('filter-modal');
        if (filterModal) filterModal.classList.add('hidden');
    });

    document.getElementById('cardSearch')?.addEventListener('input', filterCards);
    document.getElementById('filterCiv')?.addEventListener('change', filterCards);
    document.getElementById('filterType')?.addEventListener('change', filterCards);
    document.getElementById('filterCostMin')?.addEventListener('change', filterCards);
    document.getElementById('filterCostMax')?.addEventListener('change', filterCards);
    document.getElementById('clearFiltersBtn')?.addEventListener('click', clearAllFilters);

    document.getElementById('clearDeckBtn')?.addEventListener('click', () => {
        if (activeDeck.main.length > 0) {
            activeDeck = { main: [] };
            renderActiveDeck();
            showToast("Deck cleared", "info");
        }
    });

    document.getElementById('sortBy')?.addEventListener('change', (e) => {
        sortActiveDeck(e.target.value);
    });

    document.getElementById('cancelSaveBtn')?.addEventListener('click', () => {
        document.getElementById('save-modal').classList.add('hidden');
    });
    document.getElementById('confirmSaveBtn')?.addEventListener('click', finalizeSave);

    const openPlaytestBtn = document.getElementById('openPlaytestBtn');
    const closePlaytestBtn = document.getElementById('closePlaytestBtn');
    const playtestModal = document.getElementById('playtestModal');

    if (openPlaytestBtn) {
        openPlaytestBtn.addEventListener('click', () => {
            if (activeDeck.main.length === 0) {
                showToast("Please add cards to your main deck before playtesting.", "error");
                return;
            }
            playtestModal.classList.remove('hidden');
            initSimulation();
        });
    }

    document.getElementById('resetPlaytestBtn')?.addEventListener('click', () => {
        initSimulation(); // Removed any window.confirm() or alert() logic
    });

    if (closePlaytestBtn) {
        closePlaytestBtn.addEventListener('click', () => {
            playtestModal.classList.add('hidden');
        });
    }

    document.getElementById('drawCardBtn')?.addEventListener('click', () => {
        if (playtestState.deck.length > 0) {
            const card = playtestState.deck.shift();
            playtestState.hand.push(card);
            renderPlaytestZones();
        }
    });

    document.getElementById('searchDeckBtn')?.addEventListener('click', () => {
        openZoneViewer('deck');
    });

    document.getElementById('viewGraveBtn')?.addEventListener('click', () => {
        openZoneViewer('graveyard');
    });

    document.getElementById('shuffleBtn')?.addEventListener('click', () => {
        shuffleDeck(playtestState.deck);
        renderPlaytestZones();
        showToast("Deck Shuffled!", "success");
    });

    document.getElementById('closeZoneViewerBtn')?.addEventListener('click', () => {
        document.getElementById('zoneViewerModal').classList.add('hidden');
        currentViewerZone = null;
    });

    ['deck', 'mana', 'graveyard', 'shields'].forEach(zone => {
        const id = zone === 'deck' ? 'deck-pile' : `${zone}-zone`;
        const el = document.getElementById(id);
        if (el) {
            el.querySelector('.zone-label')?.addEventListener('click', (e) => {
                e.stopPropagation();
                openZoneViewer(zone);
            });
        }
    });

    document.getElementById('btnReturnTop')?.addEventListener('click', () => {
        finalizeDeckReturn('top');
    });
    document.getElementById('btnReturnBottom')?.addEventListener('click', () => {
        finalizeDeckReturn('bottom');
    });
    document.getElementById('btnReturnCancel')?.addEventListener('click', () => {
        document.getElementById('deckReturnModal').style.display = 'none';
        pendingDeckCardId = null;
        pendingSourceZone = null;
    });

    setupPlaytestDragAndDrop();
});

function clearAllFilters() {
    document.getElementById('cardSearch').value = '';
    document.getElementById('filterCiv').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterCostMin').value = '';
    document.getElementById('filterCostMax').value = '';
    filterCards();
}

function sortActiveDeck(criteria) {
    const arr = activeDeck.main;
    if (criteria === 'cost') {
        arr.sort((a, b) => a.cost - b.cost);
    } else if (criteria === 'civilization') {
        arr.sort((a, b) => {
            const civA = Array.isArray(a.civilization) ? (a.civilization[0] || '') : (a.civilization || '');
            const civB = Array.isArray(b.civilization) ? (b.civilization[0] || '') : (b.civilization || '');
            return civA.localeCompare(civB); 
        });
    } else if (criteria === 'copies') {
        const counts = {};
        arr.forEach(c => counts[c.id] = (counts[c.id] || 0) + 1);
        arr.sort((a, b) => counts[b.id] - counts[a.id] || a.name.localeCompare(b.name));
    } else if (criteria === 'order') {
        arr.sort((a, b) => a.addedAt - b.addedAt);
    }
    renderActiveDeck();
}

function filterCards() {
    const searchTerm = document.getElementById('cardSearch').value.toLowerCase();
    const civFilter = document.getElementById('filterCiv').value.toLowerCase();
    const typeFilter = document.getElementById('filterType').value.toLowerCase();
    const costMin = document.getElementById('filterCostMin').value;
    const costMax = document.getElementById('filterCostMax').value;

    const filtered = allCards.filter(card => {
        const matchesName = card.name.toLowerCase().includes(searchTerm);
        
        const civs = Array.isArray(card.civilization) ? card.civilization : (Array.isArray(card.civilizations) ? card.civilizations : [card.civilization || ""]);
        
        let matchesCiv = !civFilter;
        if (civFilter === 'multicolor') {
            matchesCiv = civs.length > 1;
        } else if (civFilter) {
            matchesCiv = civs.some(c => c.toLowerCase() === civFilter);
        }
        
        const matchesType = !typeFilter || (card.type || "").toLowerCase() === typeFilter;

        const cardCost = parseInt(card.cost) || 0;
        const matchesCostMin = costMin === "" || cardCost >= parseInt(costMin);
        const matchesCostMax = costMax === "" || cardCost <= parseInt(costMax);

        return matchesName && matchesCiv && matchesType && matchesCostMin && matchesCostMax;
    });

    renderCardLibrary(filtered);
}

function renderCardLibrary(cardData) {
    const libraryGrid = document.getElementById('card-library-grid');
    if (!libraryGrid) return;

    libraryGrid.innerHTML = '';

    cardData.forEach(card => {
        const cardItem = document.createElement('div');
        cardItem.className = 'card-item';
        cardItem.innerHTML = `
            <img src="${card.image_url}" 
                 alt="${card.name}" 
                 class="card-image" 
                 data-id="card-${card.id}" 
                 loading="lazy" 
                 onerror="this.closest('.card-item').remove();">
            <div class="card-overlay">
                <button class="btn-add" data-card-id="${card.id}">Add</button>
            </div>
        `;
        libraryGrid.appendChild(cardItem);
    });
}

function addCardToDeck(card) {
    const zoneMax = 60;
    const copyMax = 4;

    if (activeDeck.main.length >= zoneMax) {
        showToast(`Your main deck is full! Maximum ${zoneMax} cards.`, "error");
        return;
    }

    const count = activeDeck.main.filter(c => c.id === card.id).length;
    if (count >= copyMax) {
        showToast(`You cannot add more than ${copyMax} copies of "${card.name}" to your main deck.`, "error");
        return;
    }

    const cardWithMeta = { ...card, addedAt: Date.now() };
    activeDeck.main.push(cardWithMeta);
    renderActiveDeck();
}

function renderActiveDeck() {
    const zoneList = document.getElementById('active-main-deck');
    const counter = document.getElementById('count-main');
    const max = 60;

    if (!zoneList) return;

    counter.textContent = `${activeDeck.main.length} / ${max}`;
    zoneList.innerHTML = '';

    if (activeDeck.main.length === 0) {
        zoneList.innerHTML = `<p class="empty-deck-msg">No cards in main deck</p>`;
        return;
    }

    activeDeck.main.forEach((card, index) => {
        const item = document.createElement('div');
        item.className = 'deck-list-item';
        item.style.padding = '8px 10px';
        item.style.borderBottom = '1px solid #333';
        item.style.display = 'flex';
        item.style.justifyContent = 'space-between';
        item.style.alignItems = 'center';
        item.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="${card.image_url}" style="width: 30px; height: 40px; object-fit: cover; border-radius: 2px;">
                <span>${card.name}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <small style="color: var(--accent-color);">
                    ${(() => {
                        const civs = Array.isArray(card.civilization) ? card.civilization : [card.civilization];
                        return civs.length > 1 ? 'Multicolor' : (civs[0] || '');
                    })()}
                </small>
                <button class="btn-remove" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:1.2rem;">&times;</button>
            </div>
        `;

        item.querySelector('.btn-remove').onclick = () => removeCardFromDeck('main', index);
        zoneList.appendChild(item);
    });
}

function removeCardFromDeck(zone, index) {
    activeDeck[zone].splice(index, 1);
    renderActiveDeck();
}

function saveDeck() {
    const totalCards = activeDeck.main.length;
    if (totalCards === 0) {
        showToast("Your deck is empty!", "error");
        return;
    }

    const modal = document.getElementById('save-modal');
    const mainDeckName = document.getElementById('deckNameInput').value;
    
    document.getElementById('modalDeckName').value = mainDeckName;
    
    const firstCard = activeDeck.main[0];
    if (document.getElementById('modalCoverPreview')) document.getElementById('modalCoverPreview').src = firstCard ? firstCard.image_url : '';

    const coverSelect = document.getElementById('modalCoverImage');
    coverSelect.innerHTML = '';
    
    const allAddedCards = [...activeDeck.main];
    const uniqueCards = allAddedCards.filter((v, i, a) => a.findIndex(t => t.id === v.id) === i);
    
    uniqueCards.forEach(card => {
        const opt = document.createElement('option');
        opt.value = card.image_url;
        opt.textContent = card.name;
        coverSelect.appendChild(opt);
    });

    coverSelect.onchange = () => {
        document.getElementById('modalCoverPreview').src = coverSelect.value;
    };

    modal.classList.remove('hidden');
}

function finalizeSave() {
    const deckName = document.getElementById('modalDeckName').value || 'Untitled Deck';
    const coverImage = document.getElementById('modalCoverImage').value;

    const cardListPayload = {
        main: activeDeck.main.map(c => c.id)
    };

    const payload = {
        deck_id: typeof loadedDeckId !== 'undefined' ? loadedDeckId : null,
        deck_name: deckName,
        cover_image: coverImage,
        card_list: cardListPayload
    };

    fetch('save_deck.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast("Deck saved successfully!", "success");
            setTimeout(() => {
            window.location.href = 'index.php';
            }, 1000);
        } else {
            showToast('Error saving deck: ' + data.message, "error");
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showToast("Network error: Could not connect to save script.", "error");
    });
}

function shuffleDeck(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

function initSimulation() {
    playtestState.deck = activeDeck.main.map(card => ({
        ...card,
        playtest_id: Math.random().toString(36).substr(2, 9)
    }));
    
    shuffleDeck(playtestState.deck);

    playtestState.shields = playtestState.deck.splice(0, 5);
    playtestState.hand = playtestState.deck.splice(0, 5);
    playtestState.mana = [];
    playtestState.battle = [];
    playtestState.graveyard = [];

    renderPlaytestZones();
}

function renderPlaytestZones() {
    const zones = ['hand', 'mana', 'battle', 'shields', 'graveyard'];
    
    zones.forEach(zone => {
        const zoneEl = document.getElementById(zone + '-zone');
        if (!zoneEl) return;
        
        const row = zoneEl.querySelector('.card-row');
        if (!row) return;
        
        row.innerHTML = '';

        if (zone === 'battle' || zone === 'shields') {
            const baseCards = playtestState[zone].filter(c => !c.stacked_on);
            const attachedCards = playtestState[zone].filter(c => c.stacked_on);

            baseCards.forEach(baseCard => {
                const stackWrapper = document.createElement('div');
                stackWrapper.className = 'card-stack';
                stackWrapper.style.position = 'relative';
                stackWrapper.style.width = '70px';
                stackWrapper.style.height = '100px';
                stackWrapper.style.display = 'inline-block';
                stackWrapper.style.marginRight = '10px';

                const stack = [baseCard, ...attachedCards.filter(c => c.stacked_on === baseCard.playtest_id)];

                stack.forEach((card, index) => {
                    const img = createPlaytestCardElement(card, zone);
                    img.style.position = 'absolute';
                    img.style.top = (index * 15) + 'px';
                    img.style.left = (index * 5) + 'px';
                    img.style.zIndex = index + 1;
                    stackWrapper.appendChild(img);
                });
                row.appendChild(stackWrapper);
            });

            const orphans = attachedCards.filter(c => !baseCards.some(b => b.playtest_id === c.stacked_on));
            orphans.forEach(card => {
                row.appendChild(createPlaytestCardElement(card, zone));
            });

        } else {
            playtestState[zone].forEach((card, index) => {
                const img = createPlaytestCardElement(card, zone);
                if (['mana'].includes(zone) && index > 0) {
                    img.classList.add('stacked-card');
                }
                row.appendChild(img);
            });
        }
    });

    const deckCountEl = document.getElementById('deck-count');
    if (deckCountEl) deckCountEl.innerText = playtestState.deck.length;
}

function createPlaytestCardElement(card, zone) {
    const img = document.createElement('img');
    img.src = card.image_url;
    img.className = 'playtest-card';
    img.draggable = true;
    img.dataset.id = card.playtest_id;
    
    img.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('playtest_id', card.playtest_id);
        e.dataTransfer.setData('source_zone', zone);
    });

    if (['battle', 'shields'].includes(zone)) {
        img.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });

        img.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const playtestId = e.dataTransfer.getData('playtest_id');
            const sourceZone = e.dataTransfer.getData('source_zone');
            const targetId = card.playtest_id;

            if (!sourceZone) return;
            
            const cardIdx = playtestState[sourceZone].findIndex(c => c.playtest_id === playtestId);
            if (cardIdx > -1) {
                const [draggedCard] = playtestState[sourceZone].splice(cardIdx, 1);
                draggedCard.stacked_on = card.stacked_on || targetId;
                playtestState[zone].push(draggedCard);
                renderPlaytestZones();
            }
        });
    }

    return img;
}

function openZoneViewer(zoneName) {
    const modal = document.getElementById('zoneViewerModal');
    const grid = document.getElementById('zoneViewerGrid');
    const title = document.getElementById('zoneViewerTitle');
    
    if (!modal || !grid) return;
    currentViewerZone = zoneName;
    title.innerText = `Viewing: ${zoneName.toUpperCase()}`;
    grid.innerHTML = '';
    
    playtestState[zoneName].forEach(card => {
        const img = document.createElement('img');
        img.src = card.image_url;
        img.className = 'playtest-card';
        img.draggable = true;
        img.dataset.id = card.playtest_id;
        
        img.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('playtest_id', card.playtest_id);
            e.dataTransfer.setData('source_zone', zoneName);

            const viewerModal = document.getElementById('zoneViewerModal');
            if (viewerModal) {
                setTimeout(() => {
                    viewerModal.style.opacity = '0';
                    viewerModal.style.pointerEvents = 'none';
                }, 10);
            }
        });

        img.addEventListener('dragend', () => {
            const viewerModal = document.getElementById('zoneViewerModal');
            if (viewerModal) {
                viewerModal.style.opacity = '1';
                viewerModal.style.pointerEvents = 'auto';
            }
        });

        grid.appendChild(img);
    });
    modal.classList.remove('hidden');
}

function setupPlaytestDragAndDrop() {
    const zones = ['hand', 'mana', 'battle', 'shields', 'graveyard'];
    zones.forEach(zoneName => {
        const zoneEl = document.getElementById(zoneName + '-zone');
        if (!zoneEl) return;

        zoneEl.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        zoneEl.addEventListener('drop', (e) => {
            e.preventDefault();
            const playtestId = e.dataTransfer.getData('playtest_id');
            const sourceZone = e.dataTransfer.getData('source_zone');
            const targetZone = zoneName;

            if (!sourceZone) return;

            const cardIndex = playtestState[sourceZone].findIndex(c => c.playtest_id === playtestId);
            if (cardIndex > -1) {
                const [card] = playtestState[sourceZone].splice(cardIndex, 1);
                
                delete card.stacked_on;
                
                playtestState[targetZone].push(card);
                renderPlaytestZones();
                
                if (currentViewerZone === sourceZone || currentViewerZone === targetZone) {
                    openZoneViewer(currentViewerZone);
                }
            }
        });
    });

    const deckPileEl = document.getElementById('deck-pile');
    if (deckPileEl) {
        deckPileEl.addEventListener('dragover', (e) => e.preventDefault());
        deckPileEl.addEventListener('drop', (e) => {
            e.preventDefault();
            const playtestId = e.dataTransfer.getData('playtest_id');
            const sourceZone = e.dataTransfer.getData('source_zone');

            if (!sourceZone) return;
            
            pendingDeckCardId = playtestId;
            pendingSourceZone = sourceZone;
            document.getElementById('deckReturnModal').style.display = 'block';
        });
    }
}

function finalizeDeckReturn(position) {
    if (!pendingDeckCardId || !pendingSourceZone) return;

    const cardIndex = playtestState[pendingSourceZone].findIndex(c => c.playtest_id === pendingDeckCardId);
    if (cardIndex > -1) {
        const [card] = playtestState[pendingSourceZone].splice(cardIndex, 1);
        delete card.stacked_on;

        if (position === 'top') playtestState.deck.unshift(card);
        else playtestState.deck.push(card);

        renderPlaytestZones();

        if (currentViewerZone === pendingSourceZone || currentViewerZone === 'deck') {
            openZoneViewer(currentViewerZone);
        }
    }

    document.getElementById('deckReturnModal').style.display = 'none';
    pendingDeckCardId = null;
    pendingSourceZone = null;
}

document.addEventListener('mouseover', (e) => {
    const cardEl = e.target.closest('.playtest-card');
    
    if (cardEl) {
        const imgEl = cardEl.tagName === 'IMG' ? cardEl : cardEl.querySelector('img');
        if (imgEl && imgEl.src) {
            const previewContainer = document.getElementById('cardPreviewContainer');
            const previewImage = document.getElementById('cardPreviewImage');
            
            if (previewContainer && previewImage) {
                previewImage.src = imgEl.src;
                previewContainer.style.display = 'block';
            }
        }
    }
});

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast-msg ${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

document.addEventListener('mouseout', (e) => {
    const cardEl = e.target.closest('.playtest-card');
    if (cardEl) {
        const previewContainer = document.getElementById('cardPreviewContainer');
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
    }
});