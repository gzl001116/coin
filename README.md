# Coin Central Platform

Laravel + MySQL + Redis central account, SSO, points wallet, marketplace settlement platform.

Core rules: central site hides balance/recharge/consumption history; business sites can read current balance; seller finance is isolated to the business site; points cannot be withdrawn; seller withdrawals use a 5% fee; wallet mutations require idempotency keys.
