<?php
// Copy this file to config/mail.local.php and fill in your SMTP details.
// Never commit config/mail.local.php or share its credentials.
putenv('SMTP_HOST=smtp.gmail.com');
putenv('SMTP_PORT=587');
putenv('SMTP_ENCRYPTION=tls');
putenv('SMTP_USERNAME=your-sender@gmail.com');
putenv('SMTP_PASSWORD=your-google-app-password');
putenv('SMTP_FROM_EMAIL=your-sender@gmail.com');
putenv('SMTP_FROM_NAME=HomeBid');
putenv('APP_BASE_URL=http://localhost/susi');
