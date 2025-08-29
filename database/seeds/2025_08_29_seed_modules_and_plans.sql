INSERT INTO modules (slug,name,description,icon,badge_text,tier,sort_order,is_core,is_active) VALUES
('expenses','Gastos','Control de gastos','bi-cash-coin','', 'basic',1,1,1);

INSERT INTO plans (name,modules_included) VALUES
('Free','[]'),
('Pro','["expenses"]');
