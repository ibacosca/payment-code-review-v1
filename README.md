# Payment Gateway Assessment Project

**Do not fork this repository.**

This project provides a simulated payment gateway integration, designed to assess a candidate's ability to identify and resolve technical debt, understand payment flows, and apply modern PHP and Symfony best practices, including Domain-Driven Design principles.

## Your Task

You are tasked with reviewing the provided codebase and making improvements. The code simulates a payment gateway integration, including a checkout flow and refund functionality. 
Your goal is to enhance the code quality, maintainability, and overall architecture.

### Instructions

- [ ] Review the code / project and identify areas for improvement.
- [ ] Implement your proposed changes or add a detailed description of the changes you propose.
- [ ] Implement logic to do a rebilling (subscription) flow, which is not currently implemented in the codebase. This should include:
    - A new API endpoint to handle rebilling requests.
    - Logic to process rebilling based on existing payment methods.
    - Ensure that the rebilling flow is consistent with the existing payment and refund flows.
- [ ] (optional) Propose a new feature or improvement that could enhance the payment gateway integration.
- [ ] (optional) Evaluate how to implement our own vault system, which is not currently implemented in the codebase. Propose a design and detailed implementation plan for this feature.

We are interested in your thought process and your ability to justify your decisions. There is no single "right" answer, so feel free to be creative and showcase your skills.

Good luck!

### General instructions

- **Do not fork this repository.** Clone the repository and push your changes to a new branch in your own repository. This is to ensure that we can review your changes easily and other candidates do not see your work.
    *   If you are using GitHub, you can create a new repository and push your changes there.
    *   If you are using another version control system, please follow the appropriate steps to create a new branch and push your changes.
- Make sure to follow best practices.
- Pay attention to the code quality as well as software architecture. We value **maintainability** and readability.
- We recommend documenting your changes and the reasoning behind them.
- Git history is important. Make sure to commit your changes as you progress.
- Feel free to ask questions if you have any doubts.
- The task is only about seeing your skills, nothing more. It is therefore not to be expected that you will work full-time on these 7 days.

### Deliverables

- [ ] send in files with your comments / code changes by (one of)
    - drop the files anywhere and send us the link
    - upload the code to your own Repository (Avoid forking the repository and creating a PR, as this would make your solution visible to others)
- [ ] A brief report summarizing the changes you made, why, and **any additional recommendations if you had more time**.
- [ ] Approximate indication of how many hours you worked for this.

## Run instructions

### Prerequisites

* PHP 8.2+
* Composer
* Symfony CLI (optional, but recommended for `symfony server:start`)

### Installation Steps

1.  **Copy the project files:** Instead of cloning, ensure you copy all project files to your local machine.
2.  **Install Composer dependencies:**
    ```bash
    composer install
    ```
3.  **Create the database:**
    ```bash
    php bin/console doctrine:database:create
    ```
4.  **Run database migrations:**
    ```bash
    php bin/console doctrine:migrations:migrate
    ```
5.  **Start the Symfony local server (recommended):**
    ```bash
    symfony server:start
    ```
    Alternatively, configure a web server like Apache or Nginx to serve the `public/` directory.

## Usage

* **Checkout Flow:** Access the payment form at `http://127.0.0.1:8000/checkout` (or your configured URL).
* **Refunds:** Access the refund form at `http://127.0.0.1:8000/refund` (or your configured URL).
* **API Endpoints:** The API endpoints are available at `http://127.0.0.1:8000/api/` (or your configured URL). 

See `docs/openapi.yaml` for details on the API structure and documentation.
