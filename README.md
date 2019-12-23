# WordCamp Taipei 2019 Check-in
This is a customized check-in system for WordCamp Taipei 2019.

### WCTPE Check-in settings page
1. Upload and install plugin
2. Go to admin dashboard
    * Upload attendee CSV data
    * Settings
        1. WordCamp Secret Link: please contact plugin author
        2. Attendee Data File Name：File name not including full URL. (ex. camptix-export-2020-01-01.csv)
        3. Check-in Starting at
            * Format：Y-m-d H:i (ex. 2020-01-01 09:00)
            * Make sure website timezone is set. (Settings > General > Timezone)
        4. Success Check-in Redirect URL
3. Add a new page
    * Use shortcode：`[wctpe_checkin-form]`
    * Publish
