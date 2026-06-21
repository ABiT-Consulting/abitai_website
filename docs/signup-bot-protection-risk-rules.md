# Signup Bot Protection Risk Rules

This document records the SEC-04 signup protection behavior implemented in `wp-content/mu-plugins/abit-saas-auth.php`.

## Rules

`POST /api/auth/register` evaluates signup risk after payload validation and duplicate suppression, before account creation.

| Signal | Risk impact | Outcome |
| --- | ---: | --- |
| Disposable email domain such as `mailinator.com`, `tempmail.com`, or `yopmail.com` | +80 | Held for admin review |
| Free or suspicious email domain such as `gmail.com`, `outlook.com`, `.xyz`, or `.top` | +25 | Contributes to challenge threshold |
| Five or more signup attempts from the same hashed IP in one hour | +45 | Challenge required unless higher-risk signals hold it |
| Empty, very short, bot, crawler, curl, wget, Python requests, Scrapy, or headless browser user agent | +45 | Challenge required unless higher-risk signals hold it |
| Filled honeypot fields such as `website`, `url`, `homepage`, or `confirm_email_address` | +90 | Held for admin review |

Default thresholds:

| Score | Action |
| ---: | --- |
| `0-44` | Allow normal signup |
| `45-79` | Return `202 signup_challenge_required` without creating an account |
| `80-100` | Create the access request as `on_hold`, store risk metadata, set the user risk-hold meta, and do not send verification email |

The challenge response uses `bot_challenge_token` plus `bot_challenge_response=confirm_business_signup`. Tokens are bound to email, request IP, user agent, and a 15-minute time bucket.

## Acceptance Fixtures

### Low-risk signup is not blocked

Headers:

```http
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36
```

Payload:

```json
{
  "full_name": "Priya Raman",
  "business_email": "priya.raman@examplebusiness.com",
  "company_name": "Example Business LLC",
  "country_region": "AE",
  "intended_use_case": "We want to evaluate ERPNext for finance workflows and inventory visibility.",
  "password": "CorrectHorse2026!",
  "terms_privacy_acceptance": true
}
```

Expected result: generic registration accepted response, an access request in `pending_email_verification`, and a verification email token is created/sent.

### Bot-like signup is held or challenged

Challenge fixture:

```http
User-Agent: curl/8.0
```

Expected result for an otherwise valid payload: `202` with `code=signup_challenge_required`, no user or access request created until the challenge is completed.

Hold fixture:

```json
{
  "full_name": "Bot Fixture",
  "business_email": "bot-fixture@mailinator.com",
  "company_name": "Fixture Automation",
  "country_region": "US",
  "intended_use_case": "We want to test whether fake signup automation is held for review.",
  "password": "CorrectHorse2026!",
  "terms_privacy_acceptance": true,
  "website": "https://spam.example"
}
```

Expected result: generic registration accepted response, access request stored as `on_hold`, `signup_risk_action=hold`, risk reasons include disposable domain and honeypot, and no verification email is sent.
