# Secret Rotation Checklist

Use this when any real secret has been committed to the repository, even briefly.

1. Rotate `APP_KEY` first.
2. Rotate every database password that appeared in committed env files.
3. Rotate SMTP and mail provider credentials.
4. Rotate Reverb, Pusher, Twilio, Google, AWS, and any other third-party tokens that were present.
5. Invalidate old sessions, remember-me tokens, and queue workers after the new secrets are deployed.
6. Update the live server env files from the example templates in this repository.
7. Rewrite git history to remove the exposed files from old commits before sharing the repository further.
8. Force-push the cleaned history and have every collaborator re-clone or hard reset to the rewritten history.
9. Review access logs for suspicious activity between the exposure date and the rotation date.
10. Keep `.env` files untracked and use `.env.example` plus `.env.production.example` as the only committed templates.
