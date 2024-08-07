using System;
using System.Collections.Generic;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;
using System.Windows;

namespace ChatServer
{
    public partial class MainWindow : Window
    {
        private class ClientInfo
        {
            public TcpClient Client { get; set; }
            public string Username { get; set; }
            public IPAddress IPAddress { get; set; }
        }

        private List<ClientInfo> clients = new List<ClientInfo>();
        private TcpListener listener;
        private Thread listenerThread;
        private HashSet<string> sentMessages = new HashSet<string>();

        public MainWindow()
        {
            InitializeComponent();
        }

        private void StartButton_Click(object sender, RoutedEventArgs e)
        {
            string ipAddress = IpAddressTextBox.Text.Trim();
            int port;
            if (!int.TryParse(PortTextBox.Text.Trim(), out port))
            {
                MessageBox.Show("Invalid port number.");
                return;
            }

            listener = new TcpListener(IPAddress.Parse(ipAddress), port);
            listener.Start();
            StatusTextBox.Text = "Server started...";
            StartButton.IsEnabled = false;
            StopButton.IsEnabled = true;

            listenerThread = new Thread(ListenForClients);
            listenerThread.Start();
        }

        private void StopButton_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                listener.Stop();
                StatusTextBox.Text = "Server stopped...";
                StartButton.IsEnabled = true;
                StopButton.IsEnabled = false;

                foreach (var client in clients)
                {
                    try
                    {
                        NetworkStream stream = client.Client.GetStream();
                        byte[] shutdownMessage = Encoding.UTF8.GetBytes("Server is shutting down...");
                        stream.Write(shutdownMessage, 0, shutdownMessage.Length);
                    }
                    catch (Exception ex)
                    {
                        Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error sending shutdown message to {client.Username}: {ex.Message}"));
                    }

                    client.Client.Close();
                }

                clients.Clear();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error stopping server: {ex.Message}");
            }
        }


        private void ListenForClients()
        {
            while (true)
            {
                try
                {
                    TcpClient tcpClient = listener.AcceptTcpClient();
                    ClientInfo clientInfo = new ClientInfo
                    {
                        Client = tcpClient,
                        Username = $"Client{clients.Count + 1}",
                        IPAddress = ((IPEndPoint)tcpClient.Client.RemoteEndPoint).Address
                    };
                    clients.Add(clientInfo);
                    UpdateConnectedUsers();
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"{clientInfo.Username} ({clientInfo.IPAddress}) connected"));

                    Thread clientThread = new Thread(() => HandleClient(clientInfo));
                    clientThread.Start();
                }
                catch
                {
                    break;
                }
            }
        }

        private void HandleClient(ClientInfo clientInfo)
        {
            NetworkStream stream = clientInfo.Client.GetStream();
            byte[] buffer = new byte[1024];
            string message;

            while (true)
            {
                try
                {
                    int bytesRead = stream.Read(buffer, 0, buffer.Length);
                    if (bytesRead == 0)
                    {
                        // 客户端断开连接
                        break;
                    }

                    message = Encoding.UTF8.GetString(buffer, 0, bytesRead);

                    if (message.StartsWith("/name "))
                    {
                        string newUsername = message.Substring(6).Trim();
                        if (!string.IsNullOrEmpty(newUsername))
                        {
                            string oldUsername = clientInfo.Username;
                            clientInfo.Username = newUsername;
                            Dispatcher.Invoke(() => MessagesListBox.Items.Add($"{oldUsername} changed username to {clientInfo.Username}"));
                            UpdateConnectedUsers();
                        }
                    }
                    else
                    {
                        string clientMessage = $"{clientInfo.Username}: {message}";
                        if (!sentMessages.Contains(clientMessage))
                        {
                            Dispatcher.Invoke(() => MessagesListBox.Items.Add(clientMessage));
                            BroadcastMessage(clientMessage);
                            sentMessages.Add(clientMessage);
                        }
                    }
                }
                catch (IOException ex)
                {
                    // IO异常处理
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Error reading from client: {ex.Message}"));
                    break;
                }
                catch (ObjectDisposedException)
                {
                    // 对象被销毁异常处理
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Client {clientInfo.Username} disconnected unexpectedly."));
                    break;
                }
                catch (Exception ex)
                {
                    // 其他异常处理
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add($"Unexpected error: {ex.Message}"));
                    break;
                }
            }

            // 清理工作
            clients.Remove(clientInfo);
            UpdateConnectedUsers();
            Dispatcher.Invoke(() => MessagesListBox.Items.Add($"{clientInfo.Username} ({clientInfo.IPAddress}) disconnected"));
            clientInfo.Client.Close();
        }



        private void BroadcastMessage(string message)
        {
            byte[] data = Encoding.UTF8.GetBytes(message);

            foreach (ClientInfo clientInfo in clients)
            {
                try
                {
                    NetworkStream stream = clientInfo.Client.GetStream();
                    stream.Write(data, 0, data.Length);
                }
                catch
                {
                    // Handle client disconnect
                }
            }
        }

        private void UpdateConnectedUsers()
        {
            string userListMessage = "/users " + string.Join(", ", clients.ConvertAll(c => $"{c.Username} ({c.IPAddress})"));
            BroadcastMessage(userListMessage);
        }

        private void SendMessageButton_Click(object sender, RoutedEventArgs e)
        {
            string message = ServerMessageTextBox.Text;
            if (!string.IsNullOrEmpty(message))
            {
                string serverMessage = $"Server: {message}";
                if (!sentMessages.Contains(serverMessage))
                {
                    Dispatcher.Invoke(() => MessagesListBox.Items.Add(serverMessage));
                    BroadcastMessage(serverMessage);
                    sentMessages.Add(serverMessage);
                    ServerMessageTextBox.Clear();
                }
            }
        }
    }
}











