# Discord invite generator

This small app generates single-use invitations to a Discord server after authenticating a user's Reddit account and
verifying them with reCAPTCHA. Invite requests are rate-limited to one per 24 hours (by default), per username and per
IP address.

By default, the following checks are performed before a Discord invite is issued:

* The user must have a Reddit account that is at least one week old
* The user's Reddit account must have a verified email address
* The user's Reddit account must not be suspended
* The user's Reddit account must not be banned from the subreddit of your choosing
* The user must pass a reCAPTCHA validation
* The user must not have used their Reddit account to obtain an invite in the past 24 hours
* The user's IP address must not have been the source of any invite request in the past 24 hours

The Discord bot will log all issued invites to a configurable channel. Invites issued have the "temporary" flag set,
meaning users must assign a role or they will be kicked when they disconnect. Invites are also limited to a single use,
and expire after 1 hour, to limit the possibility of abuse/invite sharing.

## Installation

1. Run `composer install`
2. Copy `includes/config.php.example` to `includes/config.php` and edit it. You'll need to provision API credentials for
   Reddit, Discord and Google reCAPTCHA.
3. Initialize the database: `cd db && sqlite3 database.sqlite3 < schema.sql`
4. Use `chown`/`chmod` as appropriate to make the database writable as the webserver user

**If you are not running Apache, or do not have `AllowOverride all` set, please ensure the `includes` and `db`
directories are restricted from public access.**

## Author/License

Written by [DannyTheNinja](https://www.reddit.com/user/dannytheninja)

### BSD 3 clause license

> Copyright 2018
> 
> Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
> following conditions are met:
> 
> 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following
>    disclaimer.
> 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
>    disclaimer in the documentation and/or other materials provided with the distribution.
> 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote
>    products derived from this software without specific prior written permission.
> 
> THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
> INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
> DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
> SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
> SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
> WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
> OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
