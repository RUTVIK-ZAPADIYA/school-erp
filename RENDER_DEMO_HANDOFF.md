# School ERP Demo Handoff

## Deploy on Render

1. Push this project to a GitHub repository.
2. In Render, select **New +** and choose **Blueprint**.
3. Connect the repository and select the branch to deploy.
4. Render will read `render.yaml` and build the included `Dockerfile`.
5. Open the generated Render URL and add `/login.php` if needed.

No MySQL database or database environment variables are required for this demo deployment. When MySQL is unavailable, the application automatically uses its static demo mode.

## Demo Credentials

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `admin123` |
| Teacher | `teacher` | `teacher123` |
| Student | `student` | `student123` |

The login page also displays these credentials when demo mode is active.

## Client Link

Send the generated Render URL followed by `/login.php` to the client.

This deployment is for presentation only. Dashboard data is static demo data, and database-backed create, edit, payment, and persistence features require a real MySQL service later.
