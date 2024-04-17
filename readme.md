## Installation
0. Clone this repo: `git clone https://github.com/Vorisaj/EveTool`
1. Set environment variables
    - `EVE_HOST` (for example: evetool.com)
    - `EVE_CLIENT_ID`
    - `EVE_APP_SECRET`
    - `EVE_DB_IP`
    - `EVE_DB_USER`
    - `EVE_DB_PASSWORD`

2. Initialize tool:
    1. Navigate to `index.php` and register your first account.
    2. Go to database and manually make yourself admin: `UPDATE users SET is_admin=1;`
    3. Sign-in and navigate to `admin.php`. Import main SDE from EVE Dump.