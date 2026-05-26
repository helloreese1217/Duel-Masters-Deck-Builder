import os
import sys
import json
import time
import requests

MASTER_URL = "https://raw.githubusercontent.com/Latepate64/duel-masters-json/master/DuelMastersCards.json"
SAVE_DIR = "images/cards"
JSON_OUTPUT = "cards.json"
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
}

def fetch_master_data(url):
    try:
        response = requests.get(url, headers=HEADERS)
        response.raise_for_status()
        data = response.json()

        if isinstance(data, dict):
            if "message" in data:
                print(f"Error: GitHub API returned message: {data['message']}")
                sys.exit(1)
            for key in data.keys():
                if isinstance(data[key], list):
                    return data[key]
        return data
    except requests.exceptions.RequestException as e:
        print(f"Failed to retrieve master data: {e}")
        sys.exit(1)

def get_wiki_image_url(card_name):
    wiki_title = f"File:{card_name.replace(' ', '_')}.jpg"
    api_url = "https://duelmasters.fandom.com/api.php"
    params = {
        "action": "query",
        "titles": wiki_title,
        "prop": "imageinfo",
        "iiprop": "url",
        "format": "json"
    }

    try:
        response = requests.get(api_url, params=params, headers=HEADERS)
        response.raise_for_status()
        pages = response.json().get('query', {}).get('pages', {})
        for page_id in pages:
            if page_id != "-1":
                return pages[page_id]['imageinfo'][0]['url']
    except Exception as e:
        print(f"Metadata error for {card_name}: {e}")
    return None

def download_card_assets():
    os.makedirs(SAVE_DIR, exist_ok=True)
    cards = fetch_master_data(MASTER_URL)
    
    print(f"Processing {len(cards)} entries...")

    for i, card in enumerate(cards):
        if not isinstance(card, dict):
            continue

        card_name = card.get('name', 'Unknown')
        card_id = str(card.get('id', f'unknown_{i}')).replace('/', '_')
        file_path = os.path.join(SAVE_DIR, f"{card_id}.jpg")
        
        card['image_url'] = f"images/cards/{card_id}.jpg"

        if os.path.exists(file_path) or card_name == 'Unknown':
            continue

        img_url = get_wiki_image_url(card_name)
        
        if img_url:
            try:
                img_data = requests.get(img_url, headers=HEADERS).content
                with open(file_path, 'wb') as f:
                    f.write(img_data)
                print(f"Downloaded: {card_name} ({card_id})")
            except Exception as e:
                print(f"Download failed for {card_name}: {e}")
        else:
            print(f"Asset not found: {card_name}")

        time.sleep(0.5)

    with open(JSON_OUTPUT, 'w', encoding='utf-8') as f:
        json.dump(cards, f, indent=4)
    print(f"Process complete. Data saved to {JSON_OUTPUT}.")

if __name__ == "__main__":
    download_card_assets()