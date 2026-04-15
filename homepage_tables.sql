-- Create tables for homepage sections

-- About Section
CREATE TABLE IF NOT EXISTS about_section (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NOT NULL
);

-- Why Choose Us
CREATE TABLE IF NOT EXISTS why_choose_us (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50) NOT NULL
);

-- Commitments
CREATE TABLE IF NOT EXISTS commitments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    text TEXT NOT NULL
);

-- Stats
CREATE TABLE IF NOT EXISTS stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    label VARCHAR(255) NOT NULL,
    value VARCHAR(50) NOT NULL
);

-- Call to Action
CREATE TABLE IF NOT EXISTS cta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    heading VARCHAR(255) NOT NULL,
    subtext TEXT NOT NULL,
    button_text VARCHAR(100) NOT NULL
);

-- Insert sample data

-- About Section
INSERT INTO about_section (title, description, image) VALUES
('Craft Coffee at Its Finest', 'Welcome to Cafe Delicious, where passion meets perfection. We have been crafting exceptional coffee experiences for our beloved customers.\n\nOur expert baristas use only the finest, freshly roasted beans sourced from premium suppliers around the world. Every cup tells a story of dedication, quality, and love for the craft.\n\nWhether you are a coffee connoisseur or just looking for your daily pick-me-up, we have something special waiting for you.', 'H.jpg');

-- Why Choose Us
INSERT INTO why_choose_us (title, description, icon) VALUES
('Premium Quality', 'Handpicked, freshly roasted beans from the finest coffee regions across the globe.', '☕'),
('Expert Baristas', 'Our skilled baristas craft each cup with precision and passion for the perfect taste.', '👨‍💼'),
('Cozy Ambiance', 'Enjoy your coffee in our warm, welcoming environment perfect for work or relaxation.', '🎨'),
('Fast Service', 'Quick and efficient service without compromising on quality or taste.', '⚡'),
('Affordable Prices', 'Premium coffee experience at prices that won\'t break your budget.', '💰'),
('Sustainable', 'Eco-friendly practices ensuring a better future for the planet.', '🌍');

-- Commitments
INSERT INTO commitments (text) VALUES
('100% Fresh Ingredients - All our ingredients are sourced fresh daily. No shortcuts, no compromises.'),
('Customer Satisfaction - Your happiness is our priority. We guarantee satisfaction on every order.'),
('Hygiene & Safety - Strict hygiene standards maintained across all operations and facilities.'),
('Custom Orders - Create your perfect beverage with our customization options.'),
('Quick Delivery - Fast and reliable delivery service to get your coffee while it\'s hot.'),
('Expert Support - Our team is always ready to help with recommendations and special requests.');

-- Stats (these can be dynamic or static)
INSERT INTO stats (label, value) VALUES
('Happy Customers', '1000+'),
('Years Experience', '8+'),
('Menu Items', '50+'),
('Orders Served', '5000+');

-- Call to Action
INSERT INTO cta (heading, subtext, button_text) VALUES
('Ready to Experience Excellence?', 'Visit us today or order online for delivery to your doorstep', 'Order Coffee Now');