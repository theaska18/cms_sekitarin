-- API secrets must identify exactly one user.
ALTER TABLE `app_user`
    ADD UNIQUE KEY `uk_app_user_api_secret` (`api_secret`);
