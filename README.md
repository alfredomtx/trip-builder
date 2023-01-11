


# 🐘 What is this project?
It's a REST API for searching Flights, a much simpler version of what a real flight searching platform do.

I developed this project as a Coding Assessment to test my technical skills during a job application.

## Live endpoint and Documentation
This API is live at [xxx](), and currently anyone can register a user and use it.

There is also a full documentation of all endpoints available, where you can also easily try requests in the browser.

### 👉 [Link to documentation]()

## What the API can do:
- Search flights from airport A to B (one way trip).
- Search flights from airport A to B and B to A (round trip).
- Search flights from airport A with multiple connections(stops) until B, and vice versa.

### Search filters:
- `departure_airport`: The departure airport IATA **code**
- `arrival_airport`: The arrival airport IATA **code**.
- `departure_date`: Date of departure.
- `type`: Trip type, it can be a **one-way** or **round-trip**.
- `return_date`: Date of the return trip.
- `stops`: Number of stops, can be blank(all flights), 0(direct flights only) or 1. When 1, will filter flights with 1+ stops
- `airline`: IATA Code of the airline to filter the flights.
- `page_size`: Size per page. Defaults to 10.
- `page`: Page to view.
- `sort_by`: Sorting field, currently can be only **price**.
- `sort_order`: Sorting order, can be either **asc** or **desc**.

# 🚀 Stack
- PHP 8.1
- Laravel 8
- MySQL database

# 🧑‍💻 Usage

### Database Setup
This app uses MySQL. To use something different, open up `config/Database.php` and change the default driver.

To use MySQL, make sure you install it, setup a database and then add your db credentials(database, username and password)
to the `.env.example` file and rename it to .env`

### Migrations
To create all the necessary tables and columns, run the following
```
php artisan migrate
```

### Seeding The Database
To add the dummy data(Cities, Airports, Airlines and Flights), run the following
```
php artisan db:seed
```

### Running the App
Simply run the command below and it should be available at http://localhost/, ready for you to make requests.
```
php artisan serve
```
