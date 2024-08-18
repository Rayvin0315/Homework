package com.example.iot_chatbot_practice;
import com.google.gson.annotations.SerializedName;
import java.util.List;


public class OpenAIRequestModel {

    // 設定模型的名稱，例如 "gpt-3.5-turbo"
    @SerializedName("model") // Gson註解，指示這個變量映射到JSON中的"model"鍵
    private String model;

    // 設定對話消息的列表，每條消息包括角色和內容
    @SerializedName("messages")
    private List<Message> messages;

    // 設定生成文本的溫度，影響生成文本的隨機性
    @SerializedName("temperature")
    private float temperature;

    @SerializedName("functions")
    private List<Function> functions;

    @SerializedName("function_call")
    private String functionCall;

    // 建構函式，用於初始化OpenAIRequestModel的屬性
    public OpenAIRequestModel(String model, List<Message> messages, float temperature) {
        this.model = model;
        this.messages = messages;
        this.temperature = temperature;
    }

    public void setFunctions(List<Function> functions) {
        this.functions = functions;
    }

    public void setFunctionCall(String functionCall) {
        this.functionCall = functionCall;
    }
}

// 定義Message類別，用於表示對話中的單條消息
class Message {

    // 設定角色，例如 "user" 或 "assistant"
    @SerializedName("role")
    private String role;

    // 設定消息內容，包括用戶或助手的發言
    @SerializedName("content")
    private String content;

    // 建構函式，用於初始化Message的屬性
    public Message(String role, String content) {
        this.role = role;
        this.content = content;
    }
}