using System;
using System.IO;
using System.Net.Sockets;
using System.Text;
using System.Windows;

namespace ChatClient2
{
    public partial class MainWindow : Window
    {
        private TcpClient client;
        private NetworkStream stream;
        private string username = "Client2"; // Default username

        public MainWindow()
        {
            InitializeComponent();
            UpdateServerStatus("Offline");
        }

        private void ConnectButton_Click(object sender, RoutedEventArgs e)
        {
            string ipAddress = ServerIpTextBox.Text.Trim();
            int port;
            if (!int.TryParse(ServerPortTextBox.Text.Trim(), out port))
            {
                MessageBox.Show("Invalid port number.");
                return;
            }

            try
            {
                client = new TcpClient(ipAddress, port);
                stream = client.GetStream();
                StartListening();
                ConnectButton.IsEnabled = false;
                DisconnectButton.IsEnabled = true;
                UpdateServerStatus("Online");
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error connecting to server: " + ex.Message);
            }
        }

        private void DisconnectButton_Click(object sender, RoutedEventArgs e)
        {
            if (client != null)
            {
                try
                {
                    // 发送断开消息到服务器
                    string disconnectMessage = "Client is disconnecting";
                    byte[] data = Encoding.UTF8.GetBytes(disconnectMessage);
                    if (stream != null && stream.CanWrite)
                    {
                        stream.Write(data, 0, data.Length);
                    }
                }
                catch (IOException ex)
                {
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error sending disconnect message: {ex.Message}"));
                }
                catch (Exception ex)
                {
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error sending disconnect message: {ex.Message}"));
                }
                finally
                {
                    try
                    {
                        // 关闭流和客户端
                        stream?.Close();
                        client?.Close();
                    }
                    catch (Exception ex)
                    {
                        Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error closing connection: {ex.Message}"));
                    }
                    ConnectButton.IsEnabled = true;
                    DisconnectButton.IsEnabled = false;
                    UpdateServerStatus("Offline");
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add("Disconnected from server"));
                }
            }
        }


        private void SetUsernameButton_Click(object sender, RoutedEventArgs e)
        {
            string newUsername = UsernameTextBox.Text.Trim();
            if (!string.IsNullOrEmpty(newUsername))
            {
                username = newUsername;
                SendUsernameToServer();
                Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Username set to: {username}"));
            }
            else
            {
                MessageBox.Show("Username cannot be empty.");
            }
        }

        private void SendUsernameToServer()
        {
            if (client != null && client.Connected)
            {
                string usernameMessage = $"/name {username}";
                byte[] data = Encoding.UTF8.GetBytes(usernameMessage);
                try
                {
                    stream.Write(data, 0, data.Length);
                }
                catch (Exception ex)
                {
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error sending username to server: {ex.Message}"));
                }
            }
        }

        private async void StartListening()
        {
            byte[] buffer = new byte[1024];

            while (true)
            {
                try
                {
                    int bytesRead = await stream.ReadAsync(buffer, 0, buffer.Length);
                    if (bytesRead > 0)
                    {
                        string message = Encoding.UTF8.GetString(buffer, 0, bytesRead);
                        if (message.StartsWith("/users "))
                        {
                            string userList = message.Substring(7);
                            Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Connected users: {userList}"));
                        }
                        else
                        {
                            Dispatcher.Invoke(() => MessagesListBox.Items.Add(message));
                        }
                    }
                    else
                    {
                        UpdateServerStatus("Offline");
                        Dispatcher.Invoke(() => MessagesListBox.Items.Add("Server has shut down"));
                        break;
                    }
                }
                catch (ObjectDisposedException)
                {
                    UpdateServerStatus("Offline");
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add("Disconnected from server"));
                    break;
                }
                catch (IOException ex)
                {
                    UpdateServerStatus("Offline");
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"IO Error: {ex.Message}"));
                    break;
                }
                catch (Exception ex)
                {
                    UpdateServerStatus("Offline");
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error: {ex.Message}"));
                    break;
                }
            }
        }

        private void SendButton_Click(object sender, RoutedEventArgs e)
        {
            if (client != null && client.Connected)
            {
                string message = MessageTextBox.Text;
                if (!string.IsNullOrEmpty(message))
                {
                    string clientMessage = $"{username}: {message}";
                    byte[] data = Encoding.UTF8.GetBytes(clientMessage);
                    try
                    {
                        stream.Write(data, 0, data.Length);
                        MessageTextBox.Clear();
                    }
                    catch (Exception ex)
                    {
                        Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error sending message: {ex.Message}"));
                    }
                }
            }
        }

        private void UpdateServerStatus(string status)
        {
            Dispatcher.Invoke(() => ServerStatusTextBox.Text = $"Server Status: {status}");
        }
    }
}











