package com.example.iot_chatbot_practice;

import com.google.gson.annotations.SerializedName;
import java.util.Map;

public class Function {

    @SerializedName("name")
    private String name;

    @SerializedName("description")
    private String description;

    @SerializedName("parameters")
    private FunctionParameter parameters;

    public Function(String name, String description, FunctionParameter parameters) {
        this.name = name;
        this.description = description;
        this.parameters = parameters;
    }

    public String getName() {
        return name;
    }

    public String getDescription() {
        return description;
    }

    public FunctionParameter getParameters() {
        return parameters;
    }
}

class FunctionParameter {

    @SerializedName("type")
    private String type;

    @SerializedName("properties")
    private Map<String, FunctionParameterProperty> properties;

    public FunctionParameter(String type, Map<String, FunctionParameterProperty> properties) {
        this.type = type;
        this.properties = properties;
    }

    public String getType() {
        return type;
    }

    public Map<String, FunctionParameterProperty> getProperties() {
        return properties;
    }
}

class FunctionParameterProperty {

    @SerializedName("type")
    private String type;

    @SerializedName("description")
    private String description;

    public FunctionParameterProperty(String type, String description) {
        this.type = type;
        this.description = description;
    }

    public String getType() {
        return type;
    }

    public String getDescription() {
        return description;
    }
}