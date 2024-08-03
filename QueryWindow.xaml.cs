using System;
using System.Text;
using System.Windows;
using MySql.Data.MySqlClient;

namespace CalculatorApp
{
    public partial class QueryWindow : Window
    {
        public QueryWindow()
        {
            InitializeComponent();
        }

        private void OnShowClick(object sender, RoutedEventArgs e)
        {
            // Fetch and display all expressions from the database
            FetchAndDisplayExpressions();
        }

        private void FetchAndDisplayExpressions()
        {
            // Connection string for XAMPP MySQL server
            string connectionString = "Server=localhost;Database=calculator_db;Uid=root;Pwd=;";
            StringBuilder expressions = new StringBuilder();

            using (var connection = new MySqlConnection(connectionString))
            {
                try
                {
                    connection.Open();

                    string query = "SELECT expression FROM expressions";
                    using (var cmd = new MySqlCommand(query, connection))
                    {
                        using (var reader = cmd.ExecuteReader())
                        {
                            while (reader.Read())
                            {
                                expressions.AppendLine(reader["expression"].ToString());
                            }
                        }
                    }

                    // Display the collected expressions in the TextBox
                    ExpressionsTextBox.Text = expressions.ToString();
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error loading expressions: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }

        private void OnDeleteClick(object sender, RoutedEventArgs e)
        {
            // Prompt for confirmation before deleting
            var result = MessageBox.Show("Are you sure you want to delete all expressions?", "Confirm Delete", MessageBoxButton.YesNo, MessageBoxImage.Warning);
            if (result == MessageBoxResult.Yes)
            {
                DeleteAllExpressions();
            }
        }

        private void DeleteAllExpressions()
        {
            // Connection string for XAMPP MySQL server
            string connectionString = "Server=localhost;Database=calculator_db;Uid=root;Pwd=;";

            using (var connection = new MySqlConnection(connectionString))
            {
                try
                {
                    connection.Open();

                    string query = "DELETE FROM expressions";
                    using (var cmd = new MySqlCommand(query, connection))
                    {
                        int rowsAffected = cmd.ExecuteNonQuery();
                        MessageBox.Show($"{rowsAffected} expression(s) deleted.", "Success", MessageBoxButton.OK, MessageBoxImage.Information);

                        // Clear the TextBox after deletion
                        ExpressionsTextBox.Text = string.Empty;
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error deleting expressions: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }
    }
}

