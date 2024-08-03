using System;
using System.Collections.Generic;
using System.Text;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Navigation;
using System.Windows.Shapes;
using MySql.Data.MySqlClient;

namespace CalculatorApp
{
    public class ExpressionTreeBuilder
    {
        // Define precedence for operators
        private static readonly Dictionary<string, int> OperatorPrecedence = new Dictionary<string, int>
    {
        { "+", 1 },
        { "-", 1 },
        { "*", 2 },
        { "/", 2 }
    };

        public static TreeNode BuildExpressionTree(string infixExpression)
        {
            var operators = new Stack<string>();
            var operands = new Stack<TreeNode>();

            foreach (var token in Tokenize(infixExpression))
            {
                if (IsOperator(token))
                {
                    while (operators.Count > 0 && OperatorPrecedence[operators.Peek()] >= OperatorPrecedence[token])
                    {
                        ProcessOperator(operators, operands);
                    }
                    operators.Push(token);
                }
                else
                {
                    operands.Push(new TreeNode(token));
                }
            }

            while (operators.Count > 0)
            {
                ProcessOperator(operators, operands);
            }

            return operands.Pop();
        }

        private static IEnumerable<string> Tokenize(string expression)
        {
            var tokens = new List<string>();
            var token = "";

            foreach (var ch in expression)
            {
                if (ch == ' ')
                {
                    if (token.Length > 0)
                    {
                        tokens.Add(token);
                        token = "";
                    }
                }
                else if (IsOperator(ch.ToString()))
                {
                    if (token.Length > 0)
                    {
                        tokens.Add(token);
                        token = "";
                    }
                    tokens.Add(ch.ToString());
                }
                else
                {
                    token += ch;
                }
            }

            if (token.Length > 0)
            {
                tokens.Add(token);
            }

            return tokens;
        }

        private static bool IsOperator(string token)
        {
            return OperatorPrecedence.ContainsKey(token);
        }

        private static void ProcessOperator(Stack<string> operators, Stack<TreeNode> operands)
        {
            var op = operators.Pop();
            var right = operands.Pop();
            var left = operands.Pop();
            operands.Push(new TreeNode(op, left, right));
        }
    }
    public class TreeNode
    {
        public string Value { get; set; }
        public TreeNode Left { get; set; }
        public TreeNode Right { get; set; }

        public TreeNode(string value, TreeNode left = null, TreeNode right = null)
        {
            Value = value;
            Left = left;
            Right = right;
        }
    }
    public partial class MainWindow : Window
    {
        private TreeNode _expressionTree;
        private string _currentOperator;
        private double _firstNumber;
        private bool _isOperatorClicked;
        private string _currentExpression;
        public MainWindow()
        {
            InitializeComponent();
        }

        private void OnNumberClick(object sender, RoutedEventArgs e)
        {
            var button = sender as System.Windows.Controls.Button;
            if (button != null)
            {
                if (_isOperatorClicked)
                {
                    // Continue appending numbers without clearing
                    _isOperatorClicked = false;
                }

                // Append the number to the current expression
                if (Display.Text == "0" || _isOperatorClicked)
                {
                    Display.Text = button.Content.ToString();
                }
                else
                {
                    Display.Text += button.Content.ToString();
                }
            }
        }



        private void OnOperatorClick(object sender, RoutedEventArgs e)
        {
            var button = sender as System.Windows.Controls.Button;
            if (button != null)
            {
                if (_isOperatorClicked)
                {
                    // Replace the last operator if clicked consecutively
                    _currentExpression = _currentExpression.TrimEnd(' ', '+', '-', '*', '/');
                }

                // Append the operator to the current expression
                _currentExpression = Display.Text + " " + button.Content.ToString() + " ";

                // Update the display with the current expression
                Display.Text = _currentExpression;
                _isOperatorClicked = true;
            }
        }


        private void OnEqualClick(object sender, RoutedEventArgs e)
        {
            var expression = Display.Text;
            double result = EvaluateExpression(expression);

            // Display the result in the selected format
            DisplayResult(result);
            _isOperatorClicked = false;
        }
        private double EvaluateExpression(string expression)
        {
            var expressionTree = ExpressionTreeBuilder.BuildExpressionTree(expression);
            // Evaluate the expression tree
            return EvaluateTree(expressionTree);
        }
        private void OnInsertClick(object sender, RoutedEventArgs e)
        {
            string expression = Display.Text;

            if (!string.IsNullOrEmpty(expression))
            {
                // Insert the expression into the database
                InsertExpressionIntoDatabase(expression);
            }
            else
            {
                MessageBox.Show("No expression to insert.", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private void InsertExpressionIntoDatabase(string expression)
        {
            // Connection string for XAMPP MySQL server
            string connectionString = "Server=localhost;Database=calculator_db;Uid=root;Pwd=;";

            using (var connection = new MySqlConnection(connectionString))
            {
                try
                {
                    connection.Open();

                    // Check if the expression already exists
                    if (DoesExpressionExist(connection, expression))
                    {
                        MessageBox.Show("Expression already exists in the database.", "Duplicate Entry", MessageBoxButton.OK, MessageBoxImage.Warning);
                        return;
                    }

                    // Insert the expression into the database
                    string query = "INSERT INTO expressions (expression) VALUES (@expression)";
                    using (var cmd = new MySqlCommand(query, connection))
                    {
                        cmd.Parameters.AddWithValue("@expression", expression);
                        cmd.ExecuteNonQuery();
                    }

                    MessageBox.Show("Expression saved successfully!", "Success", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error saving expression: {ex.Message}", "Error", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }
        }
        private bool DoesExpressionExist(MySqlConnection connection, string expression)
        {
            string query = "SELECT COUNT(*) FROM expressions WHERE expression = @expression";
            using (var cmd = new MySqlCommand(query, connection))
            {
                cmd.Parameters.AddWithValue("@expression", expression);

                // Execute the query and get the count of matching rows
                long count = (long)cmd.ExecuteScalar();
                return count > 0;
            }
        }

        private double EvaluateTree(TreeNode node)
        {
            if (node == null) return 0;

            // If it's a number, return its value
            if (double.TryParse(node.Value, out var number))
            {
                return number;
            }

            // Otherwise, it's an operator; recursively evaluate left and right
            double left = EvaluateTree(node.Left);
            double right = EvaluateTree(node.Right);

            switch (node.Value)
            {
                case "+":
                    return left + right;
                case "-":
                    return left - right;
                case "*":
                    return left * right;
                case "/":
                    if (right != 0) return left / right;
                    else throw new DivideByZeroException("Division by zero is not allowed.");
            }

            throw new InvalidOperationException("Invalid operator.");
        }


        private void OnClearClick(object sender, RoutedEventArgs e)
        {
            Display.Text = "0";
            _firstNumber = 0;
            _currentOperator = null;
            _isOperatorClicked = false;
        }
        private void OnQueryClick(object sender, RoutedEventArgs e)
        {
            // Create and show the QueryWindow
            var queryWindow = new QueryWindow();
            queryWindow.Show();
        }

        private void DisplayResult(double result)
        {
            //switch (_currentDisplayFormat)
            // {
            //case "Decimal":
            DecimalDisplay.Text = result.ToString();
            //    break;
            //case "Binary":
            BinaryDispaly.Text = Convert.ToString((int)result, 2);
            //   break;
            //case "Preorder":
            var expression = $"{_firstNumber} {_currentOperator} {Display.Text}";
            var expressionTree = ExpressionTreeBuilder.BuildExpressionTree(expression);
            PreorderDisplay.Text = PreorderTraversal(expressionTree);
            //    break;
            //case "Postorder":
            PostorderDisplay.Text = PostorderTraversal(expressionTree);
                //    break;
           // }
        }


        private string PreorderTraversal(TreeNode node)
        {
            if (node == null) return "";
            return $"{node.Value} {PreorderTraversal(node.Left)} {PreorderTraversal(node.Right)}".Trim();
        }

        private string PostorderTraversal(TreeNode node)
        {
            if (node == null) return "";
            return $"{PostorderTraversal(node.Left)} {PostorderTraversal(node.Right)} {node.Value}";
        }

        private void Button_Click(object sender, RoutedEventArgs e)
        {

        }

    }
}
