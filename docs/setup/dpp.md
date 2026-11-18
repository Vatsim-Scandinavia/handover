# Data protection policy

Handover stores member data only after the member accepts your Data Protection Policy (DPP).

## How members accept the policy

When a member logs in for the first time, Handover shows a summary of your policy with a link to the full text.
Handover creates the account only when the member accepts.
Members who accepted before skip this step.

Configure the policy with these [environment variables](../reference/environment-variables.md):

- `APP_DPP_URL` links to the full policy.
- `APP_DPP_DATE` shows when you last updated it.
- `APP_DPO_MAIL` is where members ask to have their data deleted.

To change the summary itself, see [simplified Data Protection Policy](theme.md#simplified-data-protection-policy).

## Updating the policy

When you update the policy, make every member accept it again:

1. Update `APP_DPP_URL` and `APP_DPP_DATE`.
2. If needed, update the [simplified version](theme.md#simplified-data-protection-policy).
3. Reset the acceptance for all users:

    ```sh
    docker exec -it --user www-data handover php artisan reset:acceptedprivacy
    ```

4. Delete all sessions, so everyone must log in again:

    ```sh
    docker exec -it --user www-data handover sh -c 'rm -rf /app/storage/framework/sessions/*'
    ```
