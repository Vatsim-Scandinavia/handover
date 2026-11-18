---
hide:
  - navigation
  - toc
---

<!-- markdownlint-disable no-inline-html -->

# Handover for VATSIM divisions

## One login for all your division's services

Handover is an OAuth2 provider that sits between VATSIM Connect and your division's websites.
Members log in once through Handover, and your services use Handover instead of talking to VATSIM Connect directly.

<div class="grid cards" markdown>
<!-- there must be three spaces between - and content here -->
<!-- markdownlint-disable list-marker-space -->

-   :material-rocket-launch:{ .lg .middle } __Install it in production__

    ---

    Run Handover as a container next to your MySQL database

    [:octicons-arrow-right-24: Installation](installation.md)

-   :material-shield-lock:{ .lg .middle } __Keep member data in one place__

    ---

    Members accept your Data Protection Policy once, and you scrub data from one system

    [:octicons-arrow-right-24: Data protection policy](setup/dpp.md)

-   :material-connection:{ .lg .middle } __Connect your services__

    ---

    Any OAuth2 client that supports VATSIM Connect can use Handover with the same scopes and user data

    [:octicons-arrow-right-24: Connecting a client](integrations/clients.md)

-   :material-account-group:{ .lg .middle } __Manage groups__

    ---

    Assign members to groups, delegate who manages them, and share memberships with your services

    [:octicons-arrow-right-24: Groups](groups.md)

-   :material-scale-balance:{ .lg .middle } __Open Source, GPL__

    ---

    Handover is licensed under the GPL-3.0 and freely available on [GitHub]

    [:octicons-arrow-right-24: Licence](https://github.com/Vatsim-Scandinavia/handover/blob/main/LICENSE)

-   :octicons-code-of-conduct-16:{ .lg .middle } __Make it better__

    ---

    Help by contributing to [Handover itself][GitHub]

    [:octicons-arrow-right-24: Contribute](contribute.md)

</div>

Handover was created by [Daniel L.](https://github.com/blt950) (1352906) and [Matan B.](https://github.com/MatanBudimir) (1375048).
Special thanks to VATSIM UK, whose open source core system helped us on the right track.

<style type="text/css">
.md-typeset > h1 { margin-bottom: 0px; }
.md-typeset > h2 { margin-top: 0px; border-bottom: 0px; }
</style>

  [GitHub]: https://github.com/Vatsim-Scandinavia/handover
