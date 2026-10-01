# RewardLoop

A small rewards platform (OTP login + like-based points + wallet cash-out) built with intentional race condition vulnerabilities. Built for practicing race condition identification and exploitation.

Stack

* PHP 8.2
* MySQL
* Docker

Vulnerabilities

1. OTP Attempt Limit Bypass (`auth/verify_otp.php`) The attempt counter is read, then updated in a separate query, with no locking in between. Sending concurrent verification requests lets you try far more than the 3 allowed codes before the account locks.
2. Like/Unlike Race (`feed/like.php`) Checking whether a like exists and toggling it isn't atomic. Concurrent requests can double up likes and points, or send the likes counter negative through rapid unlike requests.
3. Wallet Withdrawal Race (`wallet/withdraw.php`) The points balance is checked, then deducted in a separate query (check-then-act) Concurrent withdrawal requests can all pass the balance check before any of them updates it letting you withdraw far more than your actual balance.

Run it

docker compose up --build


Then open `http://localhost:8080`
