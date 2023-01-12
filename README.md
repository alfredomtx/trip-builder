# 🐘 What is this project?
It's a REST API for searching Flights, a much simpler version of what a real flight searching platform do.

I developed this project as a Coding Assessment to test my technical skills during a job application.

**Usage** guide can be found down below, keep reading! 🙂 

## What the API can do:
- Search flights from airport A to B (one way trip).
- Search flights from airport A to B and B to A (round trip).
- Search flights from airport A with multiple connections(stops) until B, and vice versa.
- Paginated responses.

### 🔎 Search filters:
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

### 🆗 Improvement points
There are many things that could and should be improved in the project, since I had a limited time to work on it, it is not near a state-of-the-art code.

Here are a few things that could be done:
- Frontend: would be really cool to make a front end for this to consume and interact with the API!
- REST endpoints for all resources: currently only `Airline` has endpoints for CRUD operations, ideally there would be for `Airlines`, `Airports` and `Flights`.
- Sad path tests: most tests are **happy tests**, to ensure the application is working, but sad tests are important, but it also takes a bit of time.
- More sorting options: currently it is only sorting by price. Sorting by `flight duration` and `# of stops` would be interesting.

# 🚀 Stack
- PHP 8.1
- Laravel 8
- MySQL database

# 🧑‍💻 Usage

### Database Setup
This app uses MySQL. To use something different, open up `config/Database.php` and change the default driver.

To use MySQL, make sure you install it, setup a database and then add your db credentials(database, username and password)
to the `.env.example` file and rename it to `.env`.

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
Simply run the command below, and it should be available at http://localhost/, ready for you to make requests.
```
php artisan serve
```

### Documentation 
The documentation was generated using [Scribe](https://scribe.knuckles.wtf/), with the app running on localhost, you can access it in http://localhost/docs.

You can make requests to the API in the documentation page, without the need of other programs like Postman, which is pretty cool.

### Tests
There are `feature tests` and a few `unit tests` for most endpoints and important features. To run the tests, run the command below.

**PHPUnit** is set to use an **in-memory database** for the tests, so you can run the tests locally without any prior setting needed(not even MySQL configured).
```
php artisan test
```


