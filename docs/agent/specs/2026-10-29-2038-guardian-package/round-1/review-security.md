VERDICT: PASS

Security review of the whole diff found nothing at or above minor.

Checked:
- SCIM bearer auth: sha256 token hash lookup, organization match, revoked and expired refused, token_hash hidden, plain token shown once.
- SSO: OIDC id_token signature, issuer, audience and nonce verified; email_verified required to link or create (D-039); domain trust check; config encrypted and hidden; resources expose only "set" for secrets.
- Authorization: the HTTP and MCP bases, the policies and the Atrium controllers all check through Authorizer before any resolver or Action runs.
- Injection: SCIM filters are parsed to an attribute allowlist with bound values, and there is no raw SQL beyond a constant orderByRaw. The upgrade table names come from config and go through the schema builder. Views use no unescaped output.
- Bulk: operation count and payload size capped; paths restricted to Users and Groups.

Nits (not blocking): the OIDC issuer URL is fetched server-side with the https requirement relaxed in local and testing, which is inherited behaviour from Roster.
